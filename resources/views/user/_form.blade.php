@php
    $selectedRole = old('role', isset($editUser) ? $editUser->getRoleNames()->first() : null);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full Name <span class="required-field">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $editUser->name ?? '') }}" required>
        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Email <span class="required-field">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $editUser->email ?? '') }}" required>
        @error('email')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Role <span class="required-field">*</span></label>
        <select name="role" class="form-select js-select2" data-placeholder="Select role" required>
            <option value="">Select role</option>
            @foreach ($roles as $roleKey => $roleLabel)
                <option value="{{ $roleKey }}" @selected($selectedRole === $roleKey)>{{ $roleLabel }}</option>
            @endforeach
        </select>
        @error('role')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Status</label>
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="userActiveSwitch"
                @checked(old('is_active', $editUser->is_active ?? true))>
            <label class="form-check-label" for="userActiveSwitch">Active account</label>
        </div>
        <small class="text-muted d-block mt-2">Turn this off if the staff should not login for now.</small>
    </div>

    <div class="col-md-6">
        <label class="form-label">
            Password
            @if (!isset($editUser))
                <span class="required-field">*</span>
            @endif
        </label>
        <input type="password" name="password" class="form-control" placeholder="{{ isset($editUser) ? 'Leave empty if no change' : 'Enter password' }}">
        @error('password')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Confirm Password @if (!isset($editUser))<span class="required-field">*</span>@endif</label>
        <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password">
    </div>
</div>
