<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

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

                $intent['examples'] = "|\n      - " . implode("\n      - ", $examples);
                break;
            }
        }

        // Update domain responses
        $domainContent = File::get($domainPath);
        $domainData = Yaml::parse($domainContent);
        $domainData['responses']['utter_' . $request->intent_name] = [
            ['text' => trim($request->response)]
        ];

        // Save updates
        File::put($nluPath, Yaml::dump($nluData, 6, 2));
        File::put($domainPath, Yaml::dump($domainData, 6, 2));

        return redirect()->back()->with('success', 'Intent berhasil diupdate');    }

    public function trainModel()
    {
        try {
            // Build the PowerShell command for training
            $rasaCmd = 'cd "' . $this->rasaPath . '"; ';
            $rasaCmd .= '.\\rasaenv2\\Scripts\\activate.ps1; ';
            $rasaCmd .= '.\\rasaenv2\\Scripts\\rasa.exe train';

            // Execute the command through PowerShell
            $command = 'powershell.exe -Command "' . $rasaCmd . '"';
            $output = shell_exec($command);

            if ($output === null) {
                return redirect()->back()->with('error', 'Gagal melatih model. Pastikan Rasa telah terinstall dengan benar.');
            }

            // Restart Rasa server
            // $this->restartRasaServer();

            return redirect()->back()->with('success', 'Model berhasil dilatih dan server telah dimuat ulang.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
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
    //     } catch (\Exception $e) {
    //         \Log::error('Error restarting Rasa server: ' . $e->getMessage());
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
    }
}
