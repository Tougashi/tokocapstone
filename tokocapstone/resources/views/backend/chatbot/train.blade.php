@extends('backend.layouts.master')

@section('title','Train Chatbot Model')

@section('main-content')
<div class="container-fluid">
    <!-- Configuration Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-info">Konfigurasi Rasa</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Rasa Path:</strong> <code>{{ config('chatbot.rasa_path') }}</code></p>
                    <p><strong>Rasa URL:</strong> <code>{{ config('chatbot.rasa_url') }}</code></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Virtual Environment:</strong> <code>{{ config('chatbot.virtual_env') }}</code></p>
                    <p><strong>Training Timeout:</strong> <code>{{ config('chatbot.training_timeout') }} detik</code></p>
                </div>
            </div>
            <small class="text-muted">
                Konfigurasi dapat diubah melalui file .env dengan variabel RASA_PATH, RASA_URL, RASA_VIRTUAL_ENV, dan RASA_TRAINING_TIMEOUT
            </small>
        </div>
    </div>

    <!-- Training Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Train Model Chatbot</h6>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i>
                    <strong>Training Berhasil!</strong><br>
                    {!! session('success') !!}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Training Gagal!</strong><br>
                    {{session('error')}}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="fas fa-info-circle"></i>
                    {{session('info')}}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            @endif

            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Informasi Training Model</h5>
                <p>Ketika Anda melakukan training model baru:</p>
                <ul>
                    <li><strong>Validasi Data:</strong> Sistem akan memeriksa konsistensi data training</li>
                    <li><strong>Waktu Training:</strong> Proses bisa memakan waktu 5-10 menit tergantung kompleksitas data</li>
                    <li><strong>Progress Monitoring:</strong> Training progress akan dicatat dalam log file</li>
                    <li><strong>Timeout:</strong> Maximum training time: {{ config('chatbot.training_timeout') }} detik</li>
                    <li><strong>Model Baru:</strong> Setelah sukses, model baru akan tersimpan otomatis</li>
                </ul>
                <div class="alert alert-warning mt-3 mb-0">
                    <strong><i class="fas fa-exclamation-triangle"></i> Penting:</strong>
                    Jangan tutup browser atau tab ini selama proses training berlangsung!
                </div>
            </div>

            <!-- Path Validation -->
            @php
                $rasaPath = config('chatbot.rasa_path');
                $virtualEnvPath = $rasaPath . DIRECTORY_SEPARATOR . config('chatbot.virtual_env');
                $rasaExePath = $virtualEnvPath . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'rasa.exe';

                $pathExists = $rasaPath && file_exists($rasaPath);
                $virtualEnvExists = $virtualEnvPath && file_exists($virtualEnvPath);
                $rasaExeExists = $rasaExePath && file_exists($rasaExePath);
            @endphp

            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Status Validasi Path</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-1">
                                <i class="fas fa-{{ $pathExists ? 'check text-success' : 'times text-danger' }}"></i>
                                Rasa Directory
                            </p>
                            <small class="text-muted">{{ $rasaPath }}</small>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1">
                                <i class="fas fa-{{ $virtualEnvExists ? 'check text-success' : 'times text-danger' }}"></i>
                                Virtual Environment
                            </p>
                            <small class="text-muted">{{ $virtualEnvPath }}</small>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1">
                                <i class="fas fa-{{ $rasaExeExists ? 'check text-success' : 'times text-danger' }}"></i>
                                Rasa Executable
                            </p>
                            <small class="text-muted">{{ $rasaExePath }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{route('chatbot.train')}}" method="POST" class="text-center" id="trainingForm">
                @csrf
                <div class="mb-4">
                    <button type="submit" class="btn btn-primary btn-lg" id="trainButton"
                            {{ !($pathExists && $virtualEnvExists && $rasaExeExists) ? 'disabled' : '' }}>
                        <i class="fas fa-robot mr-2"></i>
                        <span id="buttonText">Train Model</span>
                    </button>

                    <!-- Testing Button -->
                    <a href="{{route('chatbot.train.simple')}}" class="btn btn-warning btn-lg ml-2"
                       {{ !($pathExists && $virtualEnvExists && $rasaExeExists) ? 'disabled' : '' }}
                       onclick="testTraining(this); return false;">
                        <i class="fas fa-flask mr-2"></i>Test Training
                    </a>

                    <!-- Emergency Actions -->
                    <div class="btn-group ml-2" role="group">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-tools"></i> Tools
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#" onclick="debugEnvironment(); return false;">
                                <i class="fas fa-bug text-info"></i> Debug Environment
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="checkStatus(); return false;">
                                <i class="fas fa-info-circle"></i> Check Status
                            </a>
                            <a class="dropdown-item" href="#" onclick="killProcesses(); return false;">
                                <i class="fas fa-stop-circle text-danger"></i> Kill Stuck Processes
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="viewLogs(); return false;">
                                <i class="fas fa-file-alt"></i> View Latest Logs
                            </a>
                        </div>
                    </div>

                    @if(!($pathExists && $virtualEnvExists && $rasaExeExists))
                        <p class="text-danger mt-2">
                            <small><i class="fas fa-exclamation-triangle"></i> Training disabled - pastikan semua path valid</small>
                        </p>
                    @else
                        <p class="text-success mt-2">
                            <small><i class="fas fa-check-circle"></i> Sistem siap untuk training</small>
                        </p>
                    @endif
                </div>

                <!-- Progress Section (hidden by default) -->
                <div id="trainingProgress" style="display: none;">
                    <div class="card">
                        <div class="card-body">
                            <h6><i class="fas fa-cogs"></i> Training in Progress...</h6>
                            <div class="progress mb-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                     role="progressbar" style="width: 0%" id="progressBar"></div>
                            </div>
                            <p id="progressText">Initializing training...</p>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> Started: <span id="startTime"></span> |
                                <i class="fas fa-hourglass-half"></i> Elapsed: <span id="elapsedTime">0s</span>
                            </small>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <!-- Training History Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Model History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Model Name</th>
                            <th>Created Date</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $modelPath = config('chatbot.rasa_path') . DIRECTORY_SEPARATOR . 'models';
                            $models = ($modelPath && file_exists($modelPath)) ? collect(File::files($modelPath))->filter(function($file) {
                                return $file->getExtension() === 'gz';
                            }) : collect([]);
                        @endphp
                        @if($models->count() > 0)
                            @foreach($models as $model)
                                <tr>
                                    <td>{{$model->getFilename()}}</td>
                                    <td>{{date('Y-m-d H:i:s', $model->getCTime())}}</td>
                                    <td>{{number_format($model->getSize() / 1024 / 1024, 2)}} MB</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    Tidak ada model ditemukan atau path models tidak valid
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Training Log Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Training Log</h6>
        </div>
        <div class="card-body">
            @php
                $logFile = storage_path('app/chatbot/training.log');
                $logContent = File::exists($logFile) ? File::get($logFile) : 'Log belum tersedia';
            @endphp
            <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto;">{{ $logContent }}</pre>
        </div>
    </div>
</div>

@push('scripts')
<script>
function testTraining(button) {
    // Disable button and show loading
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testing...';
    button.disabled = true;

    // Make AJAX request to test training
    fetch('{{route("chatbot.train.simple")}}')
        .then(response => response.json())
        .then(data => {
            button.innerHTML = originalText;
            button.disabled = false;

            if (data.success) {
                alert('✅ Test Training Berhasil!\nModel: ' + data.model);
            } else {
                alert('❌ Test Training Gagal!\nError: ' + data.error);
            }
        })
        .catch(error => {
            button.innerHTML = originalText;
            button.disabled = false;
            alert('❌ Test Training Error!\nError: ' + error.message);
        });
}

function debugEnvironment() {
    fetch('{{route("chatbot.debug")}}')
        .then(response => response.json())
        .then(data => {
            // Create a detailed debug modal or window
            let debugInfo = '🔍 RASA TRAINING DEBUG REPORT\n\n';

            debugInfo += '📁 PATHS:\n';
            debugInfo += '- Rasa Path: ' + data.rasa_path + ' (' + (data.rasa_path_exists ? '✅ EXISTS' : '❌ NOT FOUND') + ')\n';
            debugInfo += '- VEnv Path: ' + data.venv_path + ' (' + (data.venv_path_exists ? '✅ EXISTS' : '❌ NOT FOUND') + ')\n\n';

            debugInfo += '⚡ EXECUTABLES:\n';
            Object.keys(data.rasa_executables).forEach(path => {
                debugInfo += '- ' + path + ' (' + (data.rasa_executables[path] ? '✅ EXISTS' : '❌ NOT FOUND') + ')\n';
            });

            debugInfo += '\n📋 TRAINING FILES:\n';
            Object.keys(data.training_files).forEach(name => {
                const file = data.training_files[name];
                debugInfo += '- ' + name + ': ' + (file.exists ? '✅ EXISTS' : '❌ NOT FOUND') +
                           ' (Size: ' + file.size + ' bytes)\n';
            });

            debugInfo += '\n✅ VALIDATION:\n';
            debugInfo += '- Valid: ' + (data.validation.valid ? '✅ YES' : '❌ NO') + '\n';
            debugInfo += '- Message: ' + data.validation.message + '\n';

            if (data.models_dir) {
                debugInfo += '\n📦 MODELS:\n';
                debugInfo += '- Models Dir: ' + (data.models_dir.exists ? '✅ EXISTS' : '❌ NOT FOUND') + '\n';
                debugInfo += '- Models Count: ' + data.models_dir.models.length + '\n';
            }

            // Show in a scrollable alert
            const debugWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
            debugWindow.document.write(`
                <html>
                <head><title>Rasa Training Debug</title></head>
                <body style="font-family: monospace; padding: 20px; background: #1e1e1e; color: #00ff00;">
                <h2 style="color: #ffffff;">🔍 RASA TRAINING DEBUG REPORT</h2>
                <pre style="white-space: pre-wrap; line-height: 1.4;">${debugInfo}</pre>
                <hr>
                <h3 style="color: #ffffff;">📊 Raw Data:</h3>
                <pre style="white-space: pre-wrap; font-size: 12px; color: #cccccc;">${JSON.stringify(data, null, 2)}</pre>
                <button onclick="window.close()" style="margin-top: 20px; padding: 10px 20px;">Close</button>
                </body>
                </html>
            `);
        })
        .catch(error => {
            alert('❌ Debug Error: ' + error.message);
        });
}

function checkStatus() {
    fetch('{{route("chatbot.status")}}')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('❌ Error: ' + data.error);
                return;
            }

            let message = '📊 Training Status:\n';
            message += '🔄 Training Running: ' + (data.training_running ? 'YES' : 'NO') + '\n';
            message += '📦 Models Count: ' + data.models_count + '\n';
            message += '🆕 Latest Model: ' + (data.latest_model || 'None') + '\n';

            alert(message);
        })
        .catch(error => {
            alert('❌ Status Check Error: ' + error.message);
        });
}

function killProcesses() {
    if (!confirm('⚠️ Yakin ingin menghentikan semua proses training?\nIni akan menghentikan paksa semua proses Rasa yang sedang berjalan.')) {
        return;
    }

    fetch('{{route("chatbot.kill")}}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Proses berhasil dihentikan!\n' + data.message);
            } else {
                alert('❌ Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('❌ Kill Process Error: ' + error.message);
        });
}

function viewLogs() {
    // Scroll to log section
    const logSection = document.querySelector('.card:last-child');
    if (logSection) {
        logSection.scrollIntoView({ behavior: 'smooth' });
        // Highlight log section
        logSection.style.border = '3px solid #007bff';
        setTimeout(() => {
            logSection.style.border = '';
        }, 3000);
    }
}

// Enhance form submission for main training
document.getElementById('trainingForm').addEventListener('submit', function(e) {
    const button = document.getElementById('trainButton');
    const buttonText = document.getElementById('buttonText');
    const progressDiv = document.getElementById('trainingProgress');
    const startTimeSpan = document.getElementById('startTime');
    const elapsedSpan = document.getElementById('elapsedTime');

    // Show progress section
    if (progressDiv) {
        progressDiv.style.display = 'block';
        startTimeSpan.textContent = new Date().toLocaleTimeString();

        // Start elapsed time counter
        let startTime = Date.now();
        const updateElapsed = setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            elapsedSpan.textContent = elapsed + 's';
        }, 1000);

        // Store interval ID for cleanup
        window.trainingInterval = updateElapsed;
    }

    // Update button state
    button.disabled = true;
    buttonText.textContent = 'Training in Progress...';
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>' + buttonText.textContent;

    // Show warning about not closing browser
    setTimeout(() => {
        if (button.disabled) {
            alert('⚠️ Training sedang berjalan!\nJangan tutup browser atau tab ini sampai training selesai.\n\nJika training tidak selesai dalam 15 menit, gunakan "Kill Stuck Processes" di menu Tools.');
        }
    }, 3000);
});

// Clean up interval on page unload
window.addEventListener('beforeunload', function() {
    if (window.trainingInterval) {
        clearInterval(window.trainingInterval);
    }
});

// Auto-refresh status every 30 seconds if training is running
setInterval(() => {
    const button = document.getElementById('trainButton');
    if (button && button.disabled) {
        checkStatus();
    }
}, 30000);
</script>
@endpush
@endsection
