<script>
  toastr.options = {
    closeButton: true,
    progressBar: true,
    newestOnTop: true,
    positionClass: 'toast-top-right',
    timeOut: 3200,
    extendedTimeOut: 900,
    preventDuplicates: true,
    showDuration: 180,
    hideDuration: 180
  };

  @if (session('success'))
    toastr.success(@json(session('success')));
  @endif

  @if (session('error'))
    toastr.error(@json(session('error')));
  @endif

  @if ($errors->any())
    toastr.error(@json($errors->first()));
  @endif
</script>
