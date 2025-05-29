<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use App\Models\Product;

class ChatbotController extends Controller
{
    protected $rasaPath;

    public function __construct()
    {
        $this->rasaPath = base_path('../Rasa-Tokocapstone');
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
        $nluPath = $this->rasaPath . '/data/nlu.yml';
        $domainPath = $this->rasaPath . '/domain.yml';

        $nluContent = File::get($nluPath);
        $nluData = Yaml::parse($nluContent);

        // Update NLU data
        foreach ($nluData['nlu'] as &$intent) {
            if ($intent['intent'] === $request->intent_name) {
                // Format examples properly
                $examples = explode("\n", trim($request->examples));
                $examples = array_map(function($example) {
                    return trim($example);
                }, $examples);
                $examples = array_filter($examples);

                // Format examples without extra dashes
                $intent['examples'] = implode("\n", $examples);
                break;
            }
        }        // Update domain responses
        $domainContent = File::get($domainPath);
        $domainData = Yaml::parse($domainContent);
        $domainData['responses']['utter_' . $request->intent_name] = [
            ['text' => trim($request->response)]
        ];

        // Save updates
        File::put($nluPath, Yaml::dump($nluData, 6, 2));
        File::put($domainPath, Yaml::dump($domainData, 6, 2));

        return redirect()->back()->with('success', 'Intent berhasil diupdate');
    }

    public function trainModel()
    {
        try {
            // Ensure directories exist
            $storageDir = storage_path('app/chatbot');
            if (!File::exists($storageDir)) {
                File::makeDirectory($storageDir, 0755, true);
            }

            // Log training start
            $logFile = $storageDir . '/training.log';
            File::put($logFile, "Training started at: " . now() . "\n");

            // Build the PowerShell command for training with verbose output
            $rasaCmd = 'cd "' . $this->rasaPath . '"; ';
            $rasaCmd .= '.\\rasaenv2\\Scripts\\activate.ps1; ';
            $rasaCmd .= '.\\rasaenv2\\Scripts\\rasa.exe train --force --verbose';

            // Execute the command through PowerShell
            $command = 'powershell.exe -Command "' . $rasaCmd . '"';
            $output = shell_exec($command . ' 2>&1');

            // Log the output
            File::append($logFile, "Command output:\n" . $output . "\n");

            if ($output === null) {
                File::append($logFile, "Error: Command returned null\n");
                return redirect()->back()->with('error', 'Gagal melatih model. Pastikan Rasa telah terinstall dengan benar.');
            }

            // Check if training was successful by looking for model file
            $modelsDir = $this->rasaPath . '/models';
            $latestModel = $this->getLatestModel($modelsDir);

            if ($latestModel) {
                File::append($logFile, "Training completed successfully. Latest model: " . $latestModel . "\n");

                // Test the model with a simple greeting
                $testResult = $this->testModel("halo");
                File::append($logFile, "Test result: " . json_encode($testResult) . "\n");

                return redirect()->back()->with('success', 'Model berhasil dilatih! Model terbaru: ' . basename($latestModel));
            } else {
                File::append($logFile, "Error: No model file found after training\n");
                return redirect()->back()->with('error', 'Training selesai tetapi model tidak ditemukan. Periksa log untuk detail.');
            }

        } catch (\Exception $e) {
            File::append($logFile ?? storage_path('app/chatbot/training.log'), "Exception: " . $e->getMessage() . "\n");
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
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
            // Simple test by calling Rasa API
            $rasaUrl = 'http://localhost:5005/webhooks/rest/webhook';
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
            \Log::error('Error in searchLaptops: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mencari laptop',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
