@extends('backend.layouts.master')

@section('title','Manage Chatbot Intents')

@section('main-content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Manajemen Intent & Response</h6>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{session('success')}}
                </div>
            @endif

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
                                <td>{{$intent['intent']}}</td>
                                <td>
                                    <ul>
                                        @foreach(explode("\n", $intent['examples']) as $example)
                                            <li>{{trim($example, "- ")}}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>
                                    @if(isset($domain['responses']['utter_' . $intent['intent']]))
                                        {{$domain['responses']['utter_' . $intent['intent']][0]['text']}}
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm"
                                            onclick="editIntent('{{$intent['intent']}}')">
                                        Edit
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
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Intent</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{route('chatbot.updateIntent')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="intent_name" id="intent_name">

                    <div class="form-group">
                        <label>Examples (one per line)</label>
                        <textarea class="form-control" name="examples" id="examples" rows="5"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Response</label>
                        <textarea class="form-control" name="response" id="response" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
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
    $('#examples').val(intent.examples.replace(/^- /gm, ''));
    $('#response').val(response ? response[0].text : '');

    // Show modal
    $('#editIntentModal').modal('show');
}
</script>
@endpush
