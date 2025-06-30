<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;
use App\Models\Product;

class ChatbotController extends Controller
{
    protected $rasaPath;
    protected $rasaUrl;
    protected $trainingTimeout;
    protected $virtualEnv;

    public function __construct()
    {
        $this->rasaPath = config('chatbot.rasa_path');
        $this->rasaUrl = config('chatbot.rasa_url');
        $this->trainingTimeout = config('chatbot.training_timeout');
        $this->virtualEnv = config('chatbot.virtual_env');
    }

    public function intents()
    {
        $nluPath = $this->rasaPath . '/data/nlu.yml';
        $domainPath = $this->rasaPath . '/domain.yml';

        $nluContent = File::get($nluPath);
        $domainContent = File::get($domainPath);

        $nluData = Yaml::parse($nluContent);
        $domainData = Yaml::parse($domainContent);

        return view('backend.chatbot.intents', [
            'nlu' => $nluData,
            'domain' => $domainData
        ]);
    }

    public function stories()
    {
        $storiesPath = $this->rasaPath . '/data/stories.yml';
        $storiesContent = File::get($storiesPath);
        $storiesData = Yaml::parse($storiesContent);

        return view('backend.chatbot.stories', [
            'stories' => $storiesData
        ]);
    }

    public function updateIntent(Request $request)
    {
        $request->validate([
            'intent_name' => 'required|string',
            'examples' => 'required|string',
            'response' => 'required|string'
        ]);

        try {
            $nluPath = $this->rasaPath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'nlu.yml';
            $domainPath = $this->rasaPath . DIRECTORY_SEPARATOR . 'domain.yml';

            // Backup original files
            $backupDir = storage_path('app/chatbot/backups');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $timestamp = now()->format('Y-m-d_H-i-s');
            File::copy($nluPath, $backupDir . DIRECTORY_SEPARATOR . "nlu_backup_{$timestamp}.yml");
            File::copy($domainPath, $backupDir . DIRECTORY_SEPARATOR . "domain_backup_{$timestamp}.yml");

            // Update NLU data
            $nluContent = File::get($nluPath);
            $nluData = Yaml::parse($nluContent);

            $intentUpdated = false;
            foreach ($nluData['nlu'] as &$intent) {
                if ($intent['intent'] === $request->intent_name) {
                    // Format examples properly - split by newlines and clean
                    $examples = explode("\n", trim($request->examples));
                    $examples = array_map(function($example) {
                        $example = trim($example);
                        // Remove leading dash if present
                        $example = ltrim($example, '- ');
                        return trim($example);
                    }, $examples);

                    // Filter out empty examples
                    $examples = array_filter($examples, function($example) {
                        return !empty($example);
                    });

                    if (count($examples) < 2) {
                        return redirect()->back()->with('error', "Intent '{$request->intent_name}' harus memiliki minimal 2 contoh kalimat.");
                    }

                    // Format as YAML literal block
                    $intent['examples'] = "|\n" . implode("\n", array_map(function($example) {
                        return "      - " . $example;
                    }, $examples));

                    $intentUpdated = true;
                    break;
                }
            }

            if (!$intentUpdated) {
                return redirect()->back()->with('error', "Intent '{$request->intent_name}' tidak ditemukan.");
            }

            // Update domain responses
            $domainContent = File::get($domainPath);
            $domainData = Yaml::parse($domainContent);

            if (!isset($domainData['responses'])) {
                $domainData['responses'] = [];
            }

            $domainData['responses']['utter_' . $request->intent_name] = [
                ['text' => trim($request->response)]
            ];

            // Save updates with proper YAML formatting
            File::put($nluPath, Yaml::dump($nluData, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
            File::put($domainPath, Yaml::dump($domainData, 6, 2));

            // Log the update
            $logFile = storage_path('app/chatbot/intent_updates.log');
            $timestamp = now()->format('Y-m-d H:i:s');
            File::append($logFile, "[$timestamp] Intent '{$request->intent_name}' updated with " . count($examples) . " examples\n");

            // Check if should train after update
            if ($request->has('train_after_update') && $request->train_after_update) {
                // Redirect to training
                return redirect()->route('chatbot.train')->with('info', "Intent '{$request->intent_name}' berhasil diupdate. Silakan lanjutkan dengan training model.");
            }

            return redirect()->back()->with('success', "Intent '<strong>{$request->intent_name}</strong>' berhasil diupdate dengan " . count($examples) . " contoh kalimat.");

        } catch (\Exception $e) {
            // Log error
            $logFile = storage_path('app/chatbot/intent_updates.log');
            $timestamp = now()->format('Y-m-d H:i:s');
            File::append($logFile, "[$timestamp] Error updating intent '{$request->intent_name}': " . $e->getMessage() . "\n");

            return redirect()->back()->with('error', 'Error updating intent: ' . $e->getMessage());
        }
    }

    public function trainModel()
    {
        try {
            // Validate Rasa path exists
            if (!File::exists($this->rasaPath)) {
                return redirect()->back()->with('error', 'Rasa path tidak ditemukan: ' . $this->rasaPath);
            }

            // Validate virtual environment
            $virtualEnvPath = $this->rasaPath . DIRECTORY_SEPARATOR . $this->virtualEnv;
            if (!File::exists($virtualEnvPath)) {
                return redirect()->back()->with('error', 'Virtual environment tidak ditemukan: ' . $virtualEnvPath);
            }

            // Find Rasa executable
            $possibleRasaPaths = [
                $virtualEnvPath . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'rasa.exe',
                $virtualEnvPath . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'rasa',
            ];

            $rasaExePath = null;
            foreach ($possibleRasaPaths as $path) {
                if (File::exists($path)) {
                    $rasaExePath = $path;
                    break;
                }
            }

            if (!$rasaExePath) {
                return redirect()->back()->with('error', 'Rasa executable tidak ditemukan di: ' . $virtualEnvPath);
            }

            // Setup storage directory
            $storageDir = storage_path('app/chatbot');
            if (!File::exists($storageDir)) {
                File::makeDirectory($storageDir, 0755, true);
            }

            // Initialize log file
            $logFile = $storageDir . DIRECTORY_SEPARATOR . 'training.log';
            $timestamp = now()->format('Y-m-d H:i:s');
            File::put($logFile, "=== RASA TRAINING STARTED ===\n");
            File::append($logFile, "Timestamp: {$timestamp}\n");
            File::append($logFile, "Rasa Path: {$this->rasaPath}\n");
            File::append($logFile, "Virtual Env: {$virtualEnvPath}\n");
            File::append($logFile, "Rasa Executable: {$rasaExePath}\n");
            File::append($logFile, "Timeout: {$this->trainingTimeout} seconds\n\n");

            // Validate training data
            $validationResult = $this->validateTrainingData();
            if (!$validationResult['valid']) {
                File::append($logFile, "❌ Validation Failed: " . $validationResult['message'] . "\n");
                return redirect()->back()->with('error', 'Validasi gagal: ' . $validationResult['message']);
            }

            File::append($logFile, "✅ Validation passed\n");

            // Set training status to running
            $statusFile = storage_path('app/rasa_training_status.txt');
            File::put($statusFile, 'running');

            // Use simple shell_exec with output redirection
            $startTime = time();
            File::append($logFile, "🚀 Starting training process...\n");

            // Change to Rasa directory and run training
            $oldDir = getcwd();
            chdir($this->rasaPath);

            // Create PowerShell script for better output handling
            $psScript = "@echo off\n";
            $psScript .= "cd /d \"" . $this->rasaPath . "\"\n";
            $psScript .= "call \"" . $virtualEnvPath . "\\Scripts\\activate.bat\"\n";
            $psScript .= "\"" . $rasaExePath . "\" train --force\n";

            $batchFile = $storageDir . DIRECTORY_SEPARATOR . 'train.bat';
            File::put($batchFile, $psScript);

            File::append($logFile, "📝 Created batch file: {$batchFile}\n");
            File::append($logFile, "🔧 Batch content:\n{$psScript}\n");

            // Execute training with output capture
            $outputFile = $storageDir . DIRECTORY_SEPARATOR . 'train_output.log';
            $command = "\"{$batchFile}\" > \"{$outputFile}\" 2>&1";

            File::append($logFile, "⚡ Executing: {$command}\n\n");

            // Execute the command
            $startExecTime = time();
            $output = shell_exec($command);
            $execDuration = time() - $startExecTime;

            // Check for timeout
            if ($execDuration >= $this->trainingTimeout) {
                File::append($logFile, "⏰ Training timeout after {$execDuration} seconds\n");
                File::put($statusFile, 'timeout');
                return redirect()->back()->with('error',
                    "⏰ Training timeout setelah {$execDuration} detik. " .
                    "Tingkatkan RASA_TRAINING_TIMEOUT di .env atau kurangi data training."
                );
            }

            // Restore original directory
            chdir($oldDir);

            $endTime = time();
            $duration = $endTime - $startTime;

            // Read the output file
            $trainingOutput = '';
            if (File::exists($outputFile)) {
                $trainingOutput = File::get($outputFile);
                File::append($logFile, "📖 Training output:\n" . $trainingOutput . "\n");
            }

            File::append($logFile, "\n=== TRAINING COMPLETED ===\n");
            File::append($logFile, "Duration: {$duration} seconds\n");

            // Update training status
            $statusFile = storage_path('app/rasa_training_status.txt');
            File::put($statusFile, 'completed');

            // Check if model was created (this is the real success indicator)
            $modelsDir = $this->rasaPath . DIRECTORY_SEPARATOR . 'models';
            $latestModel = $this->getLatestModel($modelsDir);

            if ($latestModel) {
                $modelName = basename($latestModel);
                $modelSize = number_format(filesize($latestModel) / 1024 / 1024, 2);
                $modelTime = date('Y-m-d H:i:s', filemtime($latestModel));

                // Check if model was created in this training session
                if (filemtime($latestModel) >= $startTime - 60) { // Allow 1 minute buffer
                    File::append($logFile, "✅ Model created successfully: {$modelName}\n");
                    File::append($logFile, "📏 Model size: {$modelSize} MB\n");
                    File::append($logFile, "🕒 Model time: {$modelTime}\n");

                    // Clean up temp files
                    if (File::exists($batchFile)) File::delete($batchFile);
                    if (File::exists($outputFile)) File::delete($outputFile);
                    File::put($statusFile, 'success');

                    return redirect()->back()->with('success',
                        "🎉 Training berhasil!<br>" .
                        "📦 Model: <strong>{$modelName}</strong><br>" .
                        "📏 Ukuran: <strong>{$modelSize} MB</strong><br>" .
                        "⏱️ Waktu: <strong>{$duration} detik</strong><br>" .
                        "🕒 Dibuat: <strong>{$modelTime}</strong>"
                    );
                } else {
                    File::append($logFile, "⚠️ Found old model: {$modelName} (created: {$modelTime})\n");
                    // Check if there are any errors in output
                    if (stripos($trainingOutput, 'error') !== false || stripos($trainingOutput, 'failed') !== false) {
                        $errorMessage = $this->parseTrainingError($trainingOutput);
                        File::append($logFile, "❌ Training failed: {$errorMessage}\n");
                        File::put($statusFile, 'failed');
                        return redirect()->back()->with('error', 'Training gagal: ' . $errorMessage);
                    } else {
                        // Training might have succeeded but using old model
                        File::put($statusFile, 'warning');
                        return redirect()->back()->with('warning',
                            "⚠️ Training selesai tapi tidak ada model baru.<br>" .
                            "Model terakhir: <strong>{$modelName}</strong> ({$modelTime})<br>" .
                            "Periksa log training untuk detail."
                        );
                    }
                }
            } else {
                File::append($logFile, "❌ No model file found after training\n");
                $errorMessage = $this->parseTrainingError($trainingOutput);
                File::put($statusFile, 'failed');
                return redirect()->back()->with('error', 'Training gagal - tidak ada model yang dibuat: ' . $errorMessage);
            }

            // Clean up temp files
            if (File::exists($batchFile)) File::delete($batchFile);
            if (File::exists($outputFile)) File::delete($outputFile);

        } catch (\Exception $e) {
            $logFile = $logFile ?? storage_path('app/chatbot/training.log');
            File::append($logFile, "💥 Exception: " . $e->getMessage() . "\n");
            File::append($logFile, "📍 Stack trace:\n" . $e->getTraceAsString() . "\n");

            $statusFile = storage_path('app/rasa_training_status.txt');
            File::put($statusFile, 'error');

            return redirect()->back()->with('error', 'Error sistem: ' . $e->getMessage());
        }
    }

    // Alternative training method - more direct approach
    public function trainModelSimple()
    {
        try {
            $virtualEnvPath = $this->rasaPath . DIRECTORY_SEPARATOR . $this->virtualEnv;
            $rasaExePath = $virtualEnvPath . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'rasa.exe';

            if (!File::exists($rasaExePath)) {
                return response()->json(['error' => 'Rasa executable not found']);
            }

            // Simple direct execution
            $command = "cd /d \"{$this->rasaPath}\" && \"{$virtualEnvPath}\\Scripts\\activate.bat\" && \"{$rasaExePath}\" train --force";

            $logFile = storage_path('app/chatbot/simple_training.log');
            File::put($logFile, "Simple training started: " . now() . "\n");
            File::append($logFile, "Command: {$command}\n");

            $output = shell_exec($command . ' 2>&1');

            File::append($logFile, "Output:\n{$output}\n");

            // Check if model was created
            $modelsDir = $this->rasaPath . DIRECTORY_SEPARATOR . 'models';
            $latestModel = $this->getLatestModel($modelsDir);

            if ($latestModel && file_exists($latestModel)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Training completed',
                    'model' => basename($latestModel)
                ]);
            } else {
                return response()->json(['error' => 'Training failed or no model created']);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    // Method to kill stuck training processes
    public function killTrainingProcesses()
    {
        try {
            // Kill any stuck rasa processes
            $commands = [
                'taskkill /F /IM rasa.exe 2>nul',
                'taskkill /F /IM python.exe /FI "WINDOWTITLE eq *rasa*" 2>nul',
                'taskkill /F /IM cmd.exe /FI "WINDOWTITLE eq *rasa*" 2>nul'
            ];

            foreach ($commands as $command) {
                shell_exec($command);
            }

            return response()->json(['success' => true, 'message' => 'Processes killed']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    // Method to check training status
    public function checkTrainingStatus()
    {
        try {
            $logFile = storage_path('app/chatbot/training.log');
            $simpleLogFile = storage_path('app/chatbot/simple_training.log');

            $status = [
                'training_running' => false,
                'last_log' => '',
                'models_count' => 0,
                'latest_model' => null
            ];

            // Check if training log exists and is recent
            if (File::exists($logFile)) {
                $logModified = filemtime($logFile);
                $now = time();

                // If log was modified in last 5 minutes, training might be running
                if (($now - $logModified) < 300) {
                    $status['training_running'] = true;
                    $status['last_log'] = File::get($logFile);
                }
            }

            // Check models directory
            $modelsDir = $this->rasaPath . DIRECTORY_SEPARATOR . 'models';
            if (File::exists($modelsDir)) {
                $models = glob($modelsDir . DIRECTORY_SEPARATOR . '*.gz');
                $status['models_count'] = count($models);

                if (!empty($models)) {
                    // Get latest model
                    usort($models, function($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    $status['latest_model'] = basename($models[0]);
                }
            }

            return response()->json($status);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    private function getLatestModel($modelsDir)
    {
        if (!File::exists($modelsDir)) {
            return null;
        }

        $files = File::files($modelsDir);
        $modelFiles = array_filter($files, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'gz';
        });

        if (empty($modelFiles)) {
            return null;
        }

        // Sort by modification time
        usort($modelFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $modelFiles[0];
    }

    private function testModel($message)
    {
        try {
            // Use configurable Rasa URL
            $rasaUrl = $this->rasaUrl . '/webhooks/rest/webhook';
            $data = json_encode(['message' => $message]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $data,
                    'timeout' => 10
                ]
            ]);

            $result = file_get_contents($rasaUrl, false, $context);
            return $result ? json_decode($result, true) : null;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // private function restartRasaServer()
    // {
    //     try {
    //         // Kill existing processes
    //         shell_exec("powershell.exe -Command \"Get-Process | Where-Object {`$_.ProcessName -like '*rasa*'} | Stop-Process -Force\"");
    //         shell_exec("powershell.exe -Command \"Get-Process | Where-Object {`$_.ProcessName -eq 'python' -and `$_.MainWindowTitle -like '*rasa*'} | Stop-Process -Force\"");

    //         // Build commands for starting servers
    //         $rasaCommand = "cd '{$this->rasaPath}'; .\\rasaenv2\\Scripts\\activate.ps1; .\\rasaenv2\\Scripts\\rasa.exe run --enable-api --cors '*'";

    //         $startActionServer = "cd \"{$this->rasaPath}\"; .\\rasaenv2\\Scripts\\activate.ps1; .\\rasaenv2\\Scripts\\rasa.exe run actions";

    //         // Start servers using PowerShell Start-Process
    //         shell_exec("powershell.exe -Command \"Start-Process powershell -ArgumentList '-NoExit','-Command `\"$rasaCommand`\"'\"");
    //         shell_exec("powershell.exe -Command \"Start-Process powershell -ArgumentList '-NoExit','-Command `\"$startActionServer`\"'\"");

    //         // Give servers time to start
    //         sleep(5);
    //     } catch (\Exception $e) {    //         \Log::error('Error restarting Rasa server: ' . $e->getMessage());
    //         throw $e;
    //     }
    // }

    public function updateStory(Request $request)
    {
        $storiesPath = $this->rasaPath . '/data/stories.yml';
        $storiesContent = File::get($storiesPath);
        $storiesData = Yaml::parse($storiesContent);

        // Update or add story
        foreach ($storiesData['stories'] as &$story) {
            if ($story['story'] === $request->story_name) {
                $steps = [];
                foreach ($request->steps as $step) {
                    if (!empty($step['intent'])) {
                        $steps[] = ['intent' => $step['intent']];
                    }
                    if (!empty($step['action'])) {
                        $steps[] = ['action' => $step['action']];
                    }
                }
                $story['steps'] = $steps;
                break;
            }
        }

        // Save updates
        File::put($storiesPath, Yaml::dump($storiesData, 6, 2));

        return redirect()->back()->with('success', 'Story berhasil diupdate');
    }    public function searchLaptops(Request $request)
    {
        try {
            $query = \App\Models\Product::with(['cat_info'])
                                       ->where('status', 'active');

            // Filter by brand if provided
            if ($request->has('merek') && !empty($request->merek)) {
                $merek = strtoupper($request->merek);

                // Enhanced brand mapping untuk menangani variasi nama brand
                $brandMapping = [
                    'ASUS' => ['ASUS', 'ROG', 'REPUBLIC OF GAMERS'],
                    'ACER' => ['ACER', 'PREDATOR'],
                    'HP' => ['HP', 'HEWLETT', 'PACKARD', 'OMEN'],
                    'LENOVO' => ['LENOVO', 'THINKPAD', 'IDEAPAD', 'LEGION', 'LOQ'],
                    'DELL' => ['DELL', 'ALIENWARE', 'INSPIRON', 'XPS', 'LATITUDE'],
                    'APPLE' => ['APPLE', 'MACBOOK', 'IMAC'],
                    'MSI' => ['MSI', 'GAMING'],
                    'SAMSUNG' => ['SAMSUNG', 'GALAXY'],
                    'XIAOMI' => ['XIAOMI', 'MI', 'REDMI']
                ];

                $query->where(function($q) use ($merek, $brandMapping) {
                    // Cari di title produk
                    $q->whereRaw('UPPER(title) LIKE ?', ["%{$merek}%"])
                      ->orWhereRaw('UPPER(summary) LIKE ?', ["%{$merek}%"])
                      ->orWhereRaw('UPPER(description) LIKE ?', ["%{$merek}%"]);

                    // Cari di kategori (brand categories)
                    $q->orWhereHas('cat_info', function($catQuery) use ($merek, $brandMapping) {
                        $catQuery->whereRaw('UPPER(title) LIKE ?', ["%{$merek}%"]);

                        // Cari juga berdasarkan brand mapping
                        foreach ($brandMapping as $mainBrand => $variants) {
                            if (in_array($merek, $variants)) {
                                foreach ($variants as $variant) {
                                    $catQuery->orWhereRaw('UPPER(title) LIKE ?', ["%{$variant}%"]);
                                }
                            }
                        }
                    });

                    // Cari berdasarkan brand mapping di title produk
                    foreach ($brandMapping as $mainBrand => $variants) {
                        if (in_array($merek, $variants)) {
                            foreach ($variants as $variant) {
                                $q->orWhereRaw('UPPER(title) LIKE ?', ["%{$variant}%"])
                                  ->orWhereRaw('UPPER(summary) LIKE ?', ["%{$variant}%"]);
                            }
                        }
                    }
                });
            }

            // Filter by price if provided
            if ($request->has('harga_max') && !empty($request->harga_max)) {
                $harga_max = intval($request->harga_max);
                $query->where('price', '<=', $harga_max);
            }

            // Get results with category info and limit
            $laptops = $query->select('id', 'title as nama', 'slug', 'price as harga', 'summary as spesifikasi', 'cat_id')
                           ->orderBy('price', 'asc')
                           ->limit(10)
                           ->get();

            // Add brand extraction, product URL, and category info
            $laptops = $laptops->map(function($laptop) use ($request) {
                // Extract brand from title and category
                $title = strtoupper($laptop->nama);
                $category = $laptop->cat_info ? strtoupper($laptop->cat_info->title) : '';

                $brands = ['ASUS', 'ROG', 'ACER', 'HP', 'LENOVO', 'DELL', 'APPLE', 'MSI', 'ALIENWARE', 'SAMSUNG', 'XIAOMI'];
                $brand = 'Unknown';

                // Cek brand dari title produk
                foreach ($brands as $b) {
                    if (strpos($title, $b) !== false) {
                        $brand = $b;
                        break;
                    }
                }

                // Jika tidak ditemukan di title, cek di kategori
                if ($brand === 'Unknown' && $category) {
                    foreach ($brands as $b) {
                        if (strpos($category, $b) !== false) {
                            $brand = $b;
                            break;
                        }
                    }
                }

                // Format harga
                $laptop->harga_formatted = 'Rp ' . number_format($laptop->harga, 0, ',', '.');

                // Generate product detail URL
                $laptop->detail_url = url('/product-detail/' . $laptop->slug);

                // Add category info
                $laptop->kategori = $laptop->cat_info ? $laptop->cat_info->title : 'Tidak ada kategori';

                $laptop->merek = $brand;

                // Remove cat_id from response
                unset($laptop->cat_id);
                unset($laptop->cat_info);

                return $laptop;
            });

            // If no laptops found and brand filter was applied, return specific message
            if ($laptops->count() == 0 && $request->has('merek') && !empty($request->merek)) {
                return response()->json([
                    'success' => true,
                    'laptops' => [],
                    'total' => 0,
                    'message' => 'Tidak ada laptop ' . $request->merek . ' yang ditemukan dalam database kami. Cobalah dengan brand lain seperti ASUS, ACER, HP, LENOVO, DELL, atau APPLE.',
                    'filters' => [
                        'merek' => $request->merek,
                        'harga_max' => $request->harga_max
                    ]
                ]);
            }

            // Generate search summary message
            $searchSummary = '';
            if ($request->has('merek') && $request->has('harga_max')) {
                $searchSummary = "Ditemukan {$laptops->count()} laptop {$request->merek} dengan harga maksimal Rp " . number_format($request->harga_max, 0, ',', '.');
            } elseif ($request->has('merek')) {
                $searchSummary = "Ditemukan {$laptops->count()} laptop {$request->merek}.";
            } elseif ($request->has('harga_max')) {
                $searchSummary = "Ditemukan {$laptops->count()} laptop dengan harga maksimal Rp " . number_format($request->harga_max, 0, ',', '.');
            } else {
                $searchSummary = "Ditemukan {$laptops->count()} laptop tersedia.";
            }

            return response()->json([
                'success' => true,
                'laptops' => $laptops,
                'total' => $laptops->count(),
                'message' => $searchSummary,
                'filters' => [
                    'merek' => $request->merek,
                    'harga_max' => $request->harga_max
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in searchLaptops: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mencari laptop',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validateTrainingData()
    {
        try {
            $nluPath = $this->rasaPath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'nlu.yml';
            $storiesPath = $this->rasaPath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'stories.yml';
            $domainPath = $this->rasaPath . DIRECTORY_SEPARATOR . 'domain.yml';
            $configPath = $this->rasaPath . DIRECTORY_SEPARATOR . 'config.yml';

            // Check if required files exist
            $requiredFiles = [
                'nlu.yml' => $nluPath,
                'stories.yml' => $storiesPath,
                'domain.yml' => $domainPath,
                'config.yml' => $configPath
            ];

            foreach ($requiredFiles as $fileName => $filePath) {
                if (!File::exists($filePath)) {
                    return [
                        'valid' => false,
                        'message' => "File {$fileName} tidak ditemukan di path: {$filePath}"
                    ];
                }

                // Check if file is readable
                if (!is_readable($filePath)) {
                    return [
                        'valid' => false,
                        'message' => "File {$fileName} tidak dapat dibaca. Periksa izin akses file."
                    ];
                }
            }

            // Parse and validate YAML syntax
            $parsedFiles = [];
            try {
                $nluContent = File::get($nluPath);
                $parsedFiles['nlu'] = Yaml::parse($nluContent);

                $storiesContent = File::get($storiesPath);
                $parsedFiles['stories'] = Yaml::parse($storiesContent);

                $domainContent = File::get($domainPath);
                $parsedFiles['domain'] = Yaml::parse($domainContent);

                $configContent = File::get($configPath);
                $parsedFiles['config'] = Yaml::parse($configContent);
            } catch (\Exception $e) {
                return [
                    'valid' => false,
                    'message' => 'Error parsing YAML files: ' . $e->getMessage() . '. Periksa syntax YAML dan indentasi.'
                ];
            }

            // Validate NLU data
            if (!isset($parsedFiles['nlu']['nlu']) || empty($parsedFiles['nlu']['nlu'])) {
                return [
                    'valid' => false,
                    'message' => 'File nlu.yml tidak berisi data NLU yang valid. Pastikan ada section "nlu" dengan daftar intent.'
                ];
            }

            // Check if NLU has valid intents with examples
            $nluIntents = [];
            foreach ($parsedFiles['nlu']['nlu'] as $intent) {
                if (!isset($intent['intent']) || empty($intent['intent'])) {
                    return [
                        'valid' => false,
                        'message' => 'Ada intent tanpa nama di file nlu.yml. Setiap intent harus memiliki field "intent".'
                    ];
                }

                if (!isset($intent['examples']) || empty($intent['examples'])) {
                    return [
                        'valid' => false,
                        'message' => "Intent '{$intent['intent']}' tidak memiliki examples. Setiap intent harus memiliki minimal 2 contoh."
                    ];
                }

                // Count examples
                $exampleCount = substr_count($intent['examples'], '- ');
                if ($exampleCount < 2) {
                    return [
                        'valid' => false,
                        'message' => "Intent '{$intent['intent']}' hanya memiliki {$exampleCount} contoh. Minimal dibutuhkan 2 contoh per intent."
                    ];
                }

                $nluIntents[] = $intent['intent'];
            }

            // Validate Stories data
            if (!isset($parsedFiles['stories']['stories']) || empty($parsedFiles['stories']['stories'])) {
                return [
                    'valid' => false,
                    'message' => 'File stories.yml tidak berisi data stories yang valid. Pastikan ada section "stories" dengan daftar alur percakapan.'
                ];
            }

            // Collect all intents and actions used in stories
            $storyIntents = [];
            $storyActions = [];
            foreach ($parsedFiles['stories']['stories'] as $story) {
                if (!isset($story['story']) || empty($story['story'])) {
                    return [
                        'valid' => false,
                        'message' => 'Ada story tanpa nama di file stories.yml. Setiap story harus memiliki field "story".'
                    ];
                }

                if (!isset($story['steps']) || empty($story['steps'])) {
                    return [
                        'valid' => false,
                        'message' => "Story '{$story['story']}' tidak memiliki steps. Setiap story harus memiliki minimal 1 step."
                    ];
                }

                foreach ($story['steps'] as $step) {
                    if (isset($step['intent'])) {
                        $storyIntents[] = $step['intent'];
                    }
                    if (isset($step['action'])) {
                        $storyActions[] = $step['action'];
                    }
                }
            }

            // Validate Domain data
            if (!isset($parsedFiles['domain']['intents']) || empty($parsedFiles['domain']['intents'])) {
                return [
                    'valid' => false,
                    'message' => 'File domain.yml tidak berisi daftar intents. Pastikan ada section "intents" dengan daftar semua intent.'
                ];
            }

            // Check consistency between files
            $domainIntents = $parsedFiles['domain']['intents'];

            // Check if all NLU intents are in domain
            foreach ($nluIntents as $intent) {
                if (!in_array($intent, $domainIntents)) {
                    return [
                        'valid' => false,
                        'message' => "Intent '{$intent}' ada di nlu.yml tapi tidak ada di domain.yml. Tambahkan intent ini ke section intents di domain.yml."
                    ];
                }
            }

            // Check if story intents exist in NLU and domain
            foreach (array_unique($storyIntents) as $intent) {
                if (!in_array($intent, $nluIntents) && !in_array($intent, ['greet', 'goodbye', 'affirm', 'deny', 'mood_great', 'mood_unhappy'])) {
                    return [
                        'valid' => false,
                        'message' => "Intent '{$intent}' digunakan di stories.yml tapi tidak ada di nlu.yml. Tambahkan intent ini beserta contohnya ke nlu.yml."
                    ];
                }
            }

            // Check if story actions exist in domain
            if (isset($parsedFiles['domain']['actions'])) {
                $domainActions = $parsedFiles['domain']['actions'];
                foreach (array_unique($storyActions) as $action) {
                    if (!str_starts_with($action, 'utter_') && !in_array($action, $domainActions) && !str_starts_with($action, 'action_')) {
                        return [
                            'valid' => false,
                            'message' => "Action '{$action}' digunakan di stories.yml tapi tidak ada di domain.yml. Tambahkan action ini ke section actions di domain.yml."
                        ];
                    }
                }
            }

            // Check if responses exist for utter actions
            $utterActions = array_filter($storyActions, function($action) {
                return str_starts_with($action, 'utter_');
            });

            if (isset($parsedFiles['domain']['responses'])) {
                $domainResponses = array_keys($parsedFiles['domain']['responses']);
                foreach (array_unique($utterActions) as $utterAction) {
                    if (!in_array($utterAction, $domainResponses)) {
                        return [
                            'valid' => false,
                            'message' => "Response '{$utterAction}' digunakan di stories.yml tapi tidak ada di section responses di domain.yml."
                        ];
                    }
                }
            } else if (!empty($utterActions)) {
                return [
                    'valid' => false,
                    'message' => 'Ada utter actions di stories.yml tapi tidak ada section responses di domain.yml.'
                ];
            }

            // Validate config.yml basic structure
            if (!isset($parsedFiles['config']['recipe'])) {
                return [
                    'valid' => false,
                    'message' => 'File config.yml tidak memiliki field "recipe". Pastikan menggunakan recipe yang valid seperti "default.v1".'
                ];
            }

            if (!isset($parsedFiles['config']['language'])) {
                return [
                    'valid' => false,
                    'message' => 'File config.yml tidak memiliki field "language". Tambahkan language seperti "id" untuk Bahasa Indonesia.'
                ];
            }

            return [
                'valid' => true,
                'message' => 'Semua validasi berhasil. Data training siap untuk dilatih.',
                'stats' => [
                    'intents_count' => count($nluIntents),
                    'stories_count' => count($parsedFiles['stories']['stories']),
                    'examples_count' => array_sum(array_map(function($intent) {
                        return substr_count($intent['examples'], '- ');
                    }, $parsedFiles['nlu']['nlu']))
                ]
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error validasi: ' . $e->getMessage()
            ];
        }
    }

    private function parseTrainingError($errorOutput)
    {
        if (empty($errorOutput)) {
            return 'Error tidak diketahui terjadi selama training';
        }

        // Convert to lowercase for pattern matching
        $lowerOutput = strtolower($errorOutput);

        // Common error patterns with more detailed user-friendly messages
        $errorPatterns = [
            // YAML format errors (from our current log)
            '/doesn\'t start with.*-.*symbol.*training example will be skipped/i' => 'Format examples di nlu.yml salah. Setiap contoh harus dimulai dengan "- " (tanda minus dan spasi). Contoh: "- hello" bukan "hello".',
            '/intent.*has no examples/i' => 'Ada intent yang tidak memiliki contoh valid. Pastikan setiap intent memiliki minimal 2 contoh dengan format yang benar.',
            '/the item.*contains an example that doesn\'t start with/i' => 'Format examples salah di nlu.yml. Gunakan format YAML yang benar dengan tanda "- " untuk setiap contoh.',

            // YAML and syntax errors
            '/yaml\.scanner\.scannererror|yaml\.parser\.parsererror/i' => 'Error dalam format YAML. Periksa syntax file nlu.yml, stories.yml, atau domain.yml untuk karakter yang tidak valid atau indentasi yang salah.',
            '/yaml\.constructor\.constructorerror/i' => 'Error dalam struktur YAML. Pastikan struktur file sesuai dengan format Rasa yang benar.',

            // Training data errors
            '/no training data given|no nlu data/i' => 'Data training tidak ditemukan atau kosong. Pastikan file nlu.yml berisi intent dan examples yang valid.',
            '/no stories given|no stories data/i' => 'Data stories tidak ditemukan. Pastikan file stories.yml berisi alur percakapan yang valid.',
            '/could not load domain|domain.*not.*found/i' => 'Error dalam file domain.yml. Periksa format dan pastikan semua intent, actions, dan responses terdefinisi dengan benar.',

            // Validation errors
            '/validationerror|validation.*failed/i' => 'Error validasi data training. Periksa konsistensi antara domain.yml, nlu.yml, dan stories.yml. Pastikan semua intent dan actions terdefinisi dengan benar.',
            '/intent.*not.*found|unknown.*intent/i' => 'Ada intent yang tidak terdefinisi. Pastikan semua intent dalam stories.yml juga ada di nlu.yml dan domain.yml.',
            '/action.*not.*found|unknown.*action/i' => 'Ada action yang tidak terdefinisi. Pastikan semua action dalam stories.yml juga terdefinisi di domain.yml.',

            // File system errors
            '/filenotfounderror|no such file/i' => 'File tidak ditemukan. Periksa keberadaan file nlu.yml, stories.yml, domain.yml, dan config.yml di folder yang benar.',
            '/permissionerror|permission denied/i' => 'Akses ditolak. Periksa izin akses ke folder Rasa atau jalankan sebagai administrator.',
            '/access.*denied|cannot.*access/i' => 'Tidak dapat mengakses file atau folder. Pastikan folder tidak sedang digunakan aplikasi lain.',

            // Format errors
            '/doesn\'t start with.*-.*symbol|invalid.*example.*format/i' => 'Format contoh training salah. Setiap contoh di nlu.yml harus dimulai dengan tanda "- " (dash dan spasi).',
            '/invalid.*format|malformed/i' => 'Format file tidak valid. Periksa struktur YAML dan pastikan sesuai dengan dokumentasi Rasa.',

            // Memory and resource errors
            '/memory.*error|out.*of.*memory/i' => 'Memori tidak cukup untuk training. Coba kurangi data training atau tingkatkan RAM sistem.',
            '/disk.*full|no.*space/i' => 'Ruang disk tidak cukup. Kosongkan ruang disk atau pindah ke drive lain.',

            // Python and environment errors
            '/modulenotfounderror|no module named/i' => 'Modul Python tidak ditemukan. Pastikan virtual environment aktif dan semua dependensi terinstall.',
            '/import.*error/i' => 'Error import modul Python. Periksa instalasi Rasa dan dependensinya.',
            '/python.*not.*found/i' => 'Python tidak ditemukan. Periksa instalasi Python dan virtual environment.',

            // Network and server errors
            '/connection.*error|network.*error/i' => 'Error koneksi jaringan. Periksa koneksi internet jika training memerlukan download model.',
            '/timeout.*error/i' => 'Training timeout. Coba tingkatkan timeout atau kurangi kompleksitas data training.',

            // Model and training specific errors
            '/training.*failed|model.*training.*failed/i' => 'Training model gagal. Periksa kualitas dan konsistensi data training.',
            '/insufficient.*training.*data/i' => 'Data training tidak cukup. Tambahkan lebih banyak contoh untuk setiap intent.',
            '/epoch.*error|training.*loop.*error/i' => 'Error dalam proses training. Coba kurangi jumlah epochs di config.yml.',

            // Configuration errors
            '/config.*error|configuration.*invalid/i' => 'Error konfigurasi. Periksa file config.yml untuk parameter yang tidak valid.',
            '/pipeline.*error/i' => 'Error dalam pipeline configuration. Periksa komponen pipeline di config.yml.',

            // Generic command and execution errors
            '/command.*not.*found|is not recognized/i' => 'Command tidak dikenali. Pastikan Rasa terinstall dengan benar di virtual environment.',
            '/rasa.*exe.*not.*found/i' => 'Executable Rasa tidak ditemukan. Reinstall Rasa di virtual environment.',
            '/activate.*not.*found/i' => 'Script aktivasi virtual environment tidak ditemukan. Periksa path virtual environment.',
        ];

        // Check for specific patterns
        foreach ($errorPatterns as $pattern => $message) {
            if (preg_match($pattern, $errorOutput)) {
                return $message;
            }
        }

        // If no specific pattern matches, extract the most relevant error lines
        $lines = array_filter(explode("\n", $errorOutput), function($line) {
            $line = trim($line);
            return !empty($line) &&
                   !str_starts_with(strtolower($line), 'debug:') &&
                   !str_starts_with(strtolower($line), 'info:') &&
                   (str_contains(strtolower($line), 'error') ||
                    str_contains(strtolower($line), 'failed') ||
                    str_contains(strtolower($line), 'exception') ||
                    str_contains(strtolower($line), 'traceback'));
        });

        if (!empty($lines)) {
            // Get the most relevant error lines (last few error lines)
            $relevantLines = array_slice($lines, -3, 3);
            $errorSummary = trim(implode(' ', $relevantLines));

            // Clean up common noise in error messages
            $errorSummary = preg_replace('/\s+/', ' ', $errorSummary);
            $errorSummary = preg_replace('/rasa\.core\.|rasa\.nlu\.|rasa\./', '', $errorSummary);

            return 'Training error: ' . (strlen($errorSummary) > 200 ? substr($errorSummary, 0, 200) . '...' : $errorSummary);
        }

        // Last resort - return a generic message with truncated output
        return 'Training gagal dengan error tidak dikenal. Periksa log training untuk detail lengkap.';
    }

    // Debug method to check training environment
    public function debugTraining()
    {
        try {
            $results = [];

            // Check basic paths
            $results['rasa_path_exists'] = File::exists($this->rasaPath);
            $results['rasa_path'] = $this->rasaPath;

            $virtualEnvPath = $this->rasaPath . DIRECTORY_SEPARATOR . $this->virtualEnv;
            $results['venv_path_exists'] = File::exists($virtualEnvPath);
            $results['venv_path'] = $virtualEnvPath;

            // Check executables
            $possibleRasaPaths = [
                $virtualEnvPath . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'rasa.exe',
                $virtualEnvPath . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'rasa',
            ];

            $results['rasa_executables'] = [];
            foreach ($possibleRasaPaths as $path) {
                $results['rasa_executables'][$path] = File::exists($path);
            }

            // Check training files
            $trainingFiles = [
                'nlu.yml' => $this->rasaPath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'nlu.yml',
                'stories.yml' => $this->rasaPath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'stories.yml',
                'domain.yml' => $this->rasaPath . DIRECTORY_SEPARATOR . 'domain.yml',
                'config.yml' => $this->rasaPath . DIRECTORY_SEPARATOR . 'config.yml',
            ];

            $results['training_files'] = [];
            foreach ($trainingFiles as $name => $path) {
                $results['training_files'][$name] = [
                    'exists' => File::exists($path),
                    'path' => $path,
                    'readable' => File::exists($path) ? is_readable($path) : false,
                    'size' => File::exists($path) ? filesize($path) : 0
                ];
            }

            // Test validation
            $validationResult = $this->validateTrainingData();
            $results['validation'] = $validationResult;

            // Test simple command
            $testCmd = "cd /d \"{$this->rasaPath}\" && dir";
            $testOutput = shell_exec($testCmd);
            $results['test_command'] = [
                'command' => $testCmd,
                'output' => $testOutput
            ];

            // Check models directory
            $modelsDir = $this->rasaPath . DIRECTORY_SEPARATOR . 'models';
            $results['models_dir'] = [
                'exists' => File::exists($modelsDir),
                'path' => $modelsDir,
                'models' => File::exists($modelsDir) ? glob($modelsDir . DIRECTORY_SEPARATOR . '*.gz') : []
            ];

            return response()->json($results, 200, [], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        }
    }
}
