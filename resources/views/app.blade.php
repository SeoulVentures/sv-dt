<!DOCTYPE html>
<html lang="ko">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width,initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
		<link rel="icon" href="/favicon.ico">
		<title>{{ env('APP_NAME') }}</title>
		{{-- <link href="/js/app.js" rel="preload" as="script"><link href="/js/chunk-vendors.js" rel="preload" as="script"> --}}
        <link rel="stylesheet" href="https://uicdn.toast.com/grid/latest/tui-grid.css" />
	</head>
	<body>
		<noscript><strong>We're sorry but iad doesn't work properly without JavaScript enabled. Please enable it to continue.</strong></noscript>
		<div id="app"></div>
		<!-- built files will be auto injected -->
        <script src="{{ asset('js/app.js') }}"></script>
        {{-- <link href="{{ asset('css/app.css') }}" rel="stylesheet" /> --}}
	</body>
</html>
