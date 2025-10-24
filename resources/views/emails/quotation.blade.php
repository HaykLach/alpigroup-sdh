@if (!empty($content))
    {!! str($content)->sanitizeHtml() !!}
@endif
