@if ($errors->any())
    <div class="form-alert" role="alert">
        <x-icon name="alert" class="icon" />
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
