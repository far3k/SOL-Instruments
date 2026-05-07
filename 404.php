<?php
declare(strict_types=1);

http_response_code(404);

$base = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"] ?? "")), "/");
if ($base === "" || $base === ".") {
    $base = "";
}

$h = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, "UTF-8");
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | SOL</title>
    <style>
        :root {
            --sol-navy: #0f2744;
            --sol-navy-soft: #203858;
            --sol-accent: #1de9b6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Tahoma, "Segoe UI", sans-serif;
            background: radial-gradient(circle at 20% 20%, #f5f8fc, #e7edf5 60%, #dde6f0);
            color: #0f172a;
            display: grid;
            place-items: center;
            padding: 1rem;
        }
        .box {
            width: min(720px, 96vw);
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 16px 48px rgba(15, 39, 68, 0.16);
            overflow: hidden;
            border: 1px solid rgba(15, 39, 68, 0.08);
        }
        .head {
            background: linear-gradient(135deg, var(--sol-navy), var(--sol-navy-soft));
            color: #fff;
            padding: 1.7rem 1.4rem 1.25rem;
            display: flex;
            gap: 0.8rem;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: flex;
            gap: 0.7rem;
            align-items: center;
        }
        .brand img {
            width: 62px;
            height: 62px;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,.25));
        }
        .code {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--sol-accent);
        }
        .content { padding: 1.3rem 1.4rem 1.5rem; }
        .content h1 {
            margin: 0 0 .5rem;
            font-size: 1.35rem;
        }
        .content p {
            margin: 0 0 1rem;
            color: #334155;
            line-height: 1.8;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
        }
        .btn {
            text-decoration: none;
            border-radius: 999px;
            padding: .55rem 1rem;
            border: 1px solid transparent;
            font-size: .92rem;
        }
        .btn-primary {
            background: var(--sol-navy);
            color: #fff;
            border-color: var(--sol-navy);
        }
        .btn-light {
            background: #fff;
            color: var(--sol-navy);
            border-color: rgba(15,39,68,.2);
        }
    </style>
</head>
<body>
    <main class="box" role="main">
        <section class="head">
            <div class="brand">
                <img src="<?= $h($base) ?>/info/sol-logo.png" alt="SOL Logo">
                <strong>SOL</strong>
            </div>
            <div class="code">404</div>
        </section>
        <section class="content">
            <h1>Page not found</h1>
            <p>
                The URL you requested does not exist or may have been moved.
                Use one of the links below to continue.
            </p>
            <div class="actions">
                <a class="btn btn-primary" href="<?= $h($base) ?>/index.php">Back to home</a>
                <a class="btn btn-light" href="<?= $h($base) ?>/shop/catalog.php">Open shop</a>
                <a class="btn btn-light" href="<?= $h($base) ?>/rent/rentcatalog.php">Rent catalog</a>
            </div>
        </section>
    </main>
</body>
</html>
