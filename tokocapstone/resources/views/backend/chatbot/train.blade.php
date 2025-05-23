@extends('backend.layouts.master')

@section('title','Train Chatbot Model')

@section('main-content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Train Model Chatbot</h6>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{session('success')}}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{session('error')}}
                </div>
            @endif

            <div class="alert alert-info">
                <h5>Informasi Training Model</h5>
                <p>Ketika Anda melakukan training model baru:</p>
                <ul>
                    <li>Proses training bisa memakan waktu beberapa menit</li>
                    <li>Server Rasa akan dimuat ulang secara otomatis</li>
                    <li>Perubahan pada intents dan stories akan diterapkan setelah training selesai</li>
                </ul>
            </div>

            <form action="{{route('chatbot.train')}}" method="POST" class="text-center">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-robot mr-2"></i>Train Model
                </button>
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
                            $modelPath = base_path('../Rasa-Tokocapstone/models');
                            $models = File::files($modelPath);
                        @endphp
                        @foreach($models as $model)
                            <tr>
                                <td>{{$model->getFilename()}}</td>
                                <td>{{date('Y-m-d H:i:s', $model->getCTime())}}</td>
                                <td>{{number_format($model->getSize() / 1024 / 1024, 2)}} MB</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
