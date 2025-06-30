@extends('backend.layouts.master')

@section('title','Manage Chatbot Intents')

@section('main-content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Manajemen Intent & Response</h6>
            <div>
                <a href="{{route('chatbot.train')}}" class="btn btn-success btn-sm mr-2">
                    <i class="fas fa-robot"></i> Train Model
                </a>
                <button class="btn btn-info btn-sm" onclick="showTrainingInfo()">
                    <i class="fas fa-info-circle"></i> Info Training
                </button>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {!! session('success') !!}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{session('error')}}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            @endif

            <!-- Quick Stats -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Intents</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{count($nlu['nlu'])}}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-comments fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Examples</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        @php
                                            $totalExamples = 0;
                                            foreach($nlu['nlu'] as $intent) {
                                                $totalExamples += substr_count($intent['examples'], '- ');
                                            }
                                        @endphp
                                        {{$totalExamples}}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-list fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Responses</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{count($domain['responses'] ?? [])}}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-reply fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Model Status</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        @php
                                            $modelPath = config('chatbot.rasa_path') . DIRECTORY_SEPARATOR . 'models';
                                            $hasModel = $modelPath && file_exists($modelPath) && !empty(glob($modelPath . '/*.gz'));
                                        @endphp
                                        @if($hasModel)
                                            <span class="text-success">Ready</span>
                                        @else
                                            <span class="text-warning">Need Training</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-robot fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Intent List -->
            <div class="table-responsive">
                <table class="table table-bordered" id="intent-dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Intent</th>
                            <th>Examples</th>
                            <th>Response</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nlu['nlu'] as $intent)
                            <tr>
                                <td>
                                    <strong>{{$intent['intent']}}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {{substr_count($intent['examples'], '- ')}} examples
                                    </small>
                                </td>
                                <td>
                                    <ul class="list-unstyled mb-0">
                                        @foreach(array_slice(explode("\n", $intent['examples']), 0, 3) as $example)
                                            @if(trim($example, "- "))
                                                <li><small>{{trim($example, "- ")}}</small></li>
                                            @endif
                                        @endforeach
                                        @if(substr_count($intent['examples'], '- ') > 3)
                                            <li><small class="text-muted">...and {{substr_count($intent['examples'], '- ') - 3}} more</small></li>
                                        @endif
                                    </ul>
                                </td>
                                <td>
                                    @if(isset($domain['responses']['utter_' . $intent['intent']]))
                                        <span class="text-success">
                                            {{Str::limit($domain['responses']['utter_' . $intent['intent']][0]['text'], 100)}}
                                        </span>
                                    @else
                                        <span class="text-warning">
                                            <i class="fas fa-exclamation-triangle"></i> No response
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm"
                                            onclick="editIntent('{{$intent['intent']}}')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Intent Modal -->
<div class="modal fade" id="editIntentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Intent
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{route('chatbot.updateIntent')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="intent_name" id="intent_name">

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Petunjuk:</h6>
                        <ul class="mb-0">
                            <li>Setiap contoh kalimat harus ditulis dalam baris terpisah</li>
                            <li>Minimal 2 contoh per intent untuk hasil training yang optimal</li>
                            <li>Gunakan variasi kalimat yang berbeda untuk meningkatkan akurasi</li>
                            <li>Setelah mengubah intent, lakukan training ulang model</li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <label><strong>Examples (one per line)</strong></label>
                        <textarea class="form-control" name="examples" id="examples" rows="8"
                                placeholder="Contoh:&#10;Saya ingin beli laptop&#10;Recommend laptop dong&#10;Cari laptop yang bagus"></textarea>
                        <small class="form-text text-muted">
                            <span id="example-count">0</span> examples detected
                        </small>
                    </div>

                    <div class="form-group">
                        <label><strong>Response</strong></label>
                        <textarea class="form-control" name="response" id="response" rows="4"
                                placeholder="Response yang akan dikirim chatbot untuk intent ini"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveAndTrain()">
                        <i class="fas fa-robot"></i> Save & Train Model
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Training Info Modal -->
<div class="modal fade" id="trainingInfoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle"></i> Informasi Training Model
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-robot"></i> Tentang Training Model</h6>
                    <p class="mb-0">Training model adalah proses mengajarkan chatbot untuk memahami berbagai cara pengguna mengungkapkan maksud mereka (intent) dan memberikan respons yang tepat.</p>
                </div>

                <h6><i class="fas fa-clock"></i> Kapan Perlu Training?</h6>
                <ul>
                    <li>Setelah menambah atau mengubah intent baru</li>
                    <li>Setelah menambah atau mengubah contoh kalimat (examples)</li>
                    <li>Setelah mengubah respons chatbot</li>
                    <li>Ketika akurasi chatbot menurun</li>
                </ul>

                <h6><i class="fas fa-cogs"></i> Proses Training:</h6>
                <ol>
                    <li><strong>Validasi Data:</strong> Sistem akan memeriksa konsistensi data training</li>
                    <li><strong>Training Model:</strong> Rasa akan melatih model dengan data yang ada (5-10 menit)</li>
                    <li><strong>Model Baru:</strong> Model baru akan tersimpan dan siap digunakan</li>
                </ol>

                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Penting!</h6>
                    <ul class="mb-0">
                        <li>Pastikan setiap intent memiliki minimal 2 contoh kalimat</li>
                        <li>Gunakan variasi kalimat yang beragam untuk hasil yang optimal</li>
                        <li>Training memerlukan waktu beberapa menit, mohon bersabar</li>
                        <li>Jangan tutup browser selama proses training berlangsung</li>
                    </ul>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6><i class="fas fa-check-circle text-success"></i> Best Practices:</h6>
                        <ul>
                            <li>Minimal 5-10 contoh per intent</li>
                            <li>Gunakan bahasa yang natural</li>
                            <li>Sertakan variasi kata dan struktur kalimat</li>
                            <li>Test model setelah training</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-times-circle text-danger"></i> Hindari:</h6>
                        <ul>
                            <li>Contoh kalimat yang terlalu mirip</li>
                            <li>Menggunakan singkatan berlebihan</li>
                            <li>Intent dengan hanya 1 contoh</li>
                            <li>Respons yang terlalu panjang</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <a href="{{route('chatbot.train')}}" class="btn btn-success">
                    <i class="fas fa-robot"></i> Go to Training Page
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function editIntent(intentName) {
    // Find intent data
    let intent = {!! json_encode($nlu['nlu']) !!}.find(i => i.intent === intentName);
    let response = {!! json_encode($domain['responses']) !!}['utter_' + intentName];

    // Populate modal
    $('#intent_name').val(intentName);

    // Clean examples text
    let examplesText = intent.examples.replace(/^\s*-\s*/gm, '').trim();
    $('#examples').val(examplesText);

    $('#response').val(response ? response[0].text : '');

    // Update example count
    updateExampleCount();

    // Show modal
    $('#editIntentModal').modal('show');
}

function showTrainingInfo() {
    $('#trainingInfoModal').modal('show');
}

function saveAndTrain() {
    // Submit form first, then redirect to training
    let form = $('#editIntentModal form');
    form.append('<input type="hidden" name="train_after_update" value="1">');
    form.submit();
}

function updateExampleCount() {
    let examples = $('#examples').val();
    let count = examples.split('\n').filter(line => line.trim() !== '').length;
    $('#example-count').text(count);
}

// Update example count on input
$(document).ready(function() {
    $('#examples').on('input', updateExampleCount);

    // Initialize DataTable with better options
    $('#intent-dataTable').DataTable({
        "pageLength": 10,
        "responsive": true,
        "order": [[0, "asc"]],
        "columnDefs": [
            {"width": "15%", "targets": 0},
            {"width": "35%", "targets": 1},
            {"width": "35%", "targets": 2},
            {"width": "15%", "targets": 3}
        ]
    });
});
</script>
@endpush
