<!DOCTYPE html>
<html>
<head>
    <title>Button Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Button Test</h2>
    
    <div class="mb-3">
        <label>Test Input:</label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" class="form-control" id="testInput" placeholder="Enter amount">
            <button class="btn btn-primary" type="button" id="testButton">Test Button</button>
        </div>
    </div>
    
    <div id="message" class="alert" style="display: none;"></div>
    
    <script>
    const testBtn = document.getElementById('testButton');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            const value = document.getElementById('testInput').value;
            const msg = document.getElementById('message');
            msg.textContent = 'Button clicked! Value: ' + value;
            msg.className = 'alert alert-success';
            msg.style.display = 'block';
        });
    } else {
        console.error('Button not found!');
    }
    </script>
</div>
</body>
</html>
