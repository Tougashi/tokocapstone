@extends('backend.layouts.master')

@section('title','Manage Chatbot Stories')

@section('main-content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Manajemen Stories</h6>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{session('success')}}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered" id="stories-dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Story Name</th>
                            <th>Steps</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stories['stories'] as $story)
                            <tr>
                                <td>{{$story['story'] ?? 'Unnamed Story'}}</td>
                                <td>
                                    <ul>
                                        @foreach($story['steps'] as $step)
                                            <li>
                                                @if(isset($step['intent']))
                                                    User: {{$step['intent']}}
                                                @endif
                                                @if(isset($step['action']))
                                                    Bot: {{$step['action']}}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm"
                                            onclick="editStory('{{$story['story'] ?? ''}}')">
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

<!-- Edit Story Modal -->
<div class="modal fade" id="editStoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Story</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{route('chatbot.updateStory')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="story_name" id="story_name">

                    <div class="form-group">
                        <label>Story Steps</label>
                        <div id="storySteps">
                            <!-- Steps will be added dynamically -->
                        </div>
                        <button type="button" class="btn btn-info btn-sm mt-2" onclick="addStep()">
                            Add Step
                        </button>
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
let stories = {!! json_encode($stories['stories']) !!};

function editStory(storyName) {
    let story = stories.find(s => s.story === storyName);
    if (!story) return;

    $('#story_name').val(storyName);
    $('#storySteps').empty();

    story.steps.forEach((step, index) => {
        addStep(step);
    });

    $('#editStoryModal').modal('show');
}

function addStep(step = null) {
    let stepHtml = `
        <div class="step-row mb-3">
            <div class="row">
                <div class="col-5">
                    <input type="text" class="form-control" name="steps[${$('.step-row').length}][intent]"
                           placeholder="User Intent" value="${step?.intent || ''}">
                </div>
                <div class="col-5">
                    <input type="text" class="form-control" name="steps[${$('.step-row').length}][action]"
                           placeholder="Bot Action" value="${step?.action || ''}">
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeStep(this)">
                        Remove
                    </button>
                </div>
            </div>
        </div>
    `;
    $('#storySteps').append(stepHtml);
}

function removeStep(button) {
    $(button).closest('.step-row').remove();
}
</script>
@endpush
