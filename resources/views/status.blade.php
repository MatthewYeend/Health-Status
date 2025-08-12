<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Health Status</title>
  <style>
    body { font-family: sans-serif; margin: 2em; }
    .ok { color: green; }
    .warning { color: orange; }
    .critical { color: red; }
    pre { background: #f4f4f4; padding: 1em; }
  </style>
</head>
<body>
  <h1>Status: <span class="{{ $data['status'] }}">{{ strtoupper($data['status']) }}</span></h1>
  <p>Checked at: {{ $data['timestamp'] }}</p>
  @foreach($data['checks'] as $name => $check)
    <h2>{{ ucfirst($name) }} — <span class="{{ $check['status'] }}">{{ $check['status'] }}</span></h2>
    <pre>{{ json_encode($check, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
  @endforeach
</body>
</html>
