<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Options</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .options-container {
            text-align: center;
        }

        h1 {
            margin-bottom: 20px;
        }

        .buttons {
            display: flex;
            gap: 30px;
            justify-content: center;
            margin-top: 20px;
        }

        .option-btn {
            display: inline-block;
            width: 300px;   
            height: 200px; 
            font-size: 36px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            border-radius: 15px;
            color: #fff;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .in-btn {
            background-color: #28a745;
        }
        .in-btn:hover {
            background-color: #218838;
        }

        .return-btn {
            background-color: #dc3545; 
        }
        .return-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="options-container">
        <div class="buttons">
            <a href="{{ route('index')}}" class="option-btn in-btn">IN</a>
            <a href="{{ route('production-panel-return')}}" class="option-btn return-btn">RETURN</a>
        </div>
    </div>
</body>
</html>