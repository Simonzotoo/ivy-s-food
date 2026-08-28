@if (session('status'))
    <script>document.addEventListener('DOMContentLoaded', () => window.ivyToast(@js(session('status'))));</script>
@endif
@if ($errors->any())
    <script>document.addEventListener('DOMContentLoaded', () => window.ivyError(@js($errors->all())));</script>
@endif
