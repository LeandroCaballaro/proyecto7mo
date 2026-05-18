<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="Frontend/style/styles.css" rel="stylesheet">
    <title>Log In</title>
</head>
<body>
    <?php include 'Frontend/header.php'; ?>
    <div class="contenedor">
        <div class="rounded-xl border border-border bg-card p-6">
            <div class="aspect-[2/3] mb-4 rounded-lg bg-secondary"></div>
            <h3 class="text-xl font-semibold text-foreground">Log In</h3>
            <p class="text-muted-foreground">Usuario: </p> <input type="text" class="input" placeholder="Ingrese su Usuario">
            <p class="text-muted-foreground">Contraseña: </p> <input type="password" class="input" placeholder="Ingrese su Contraseña">
            <button class="mt-4 w-full rounded-lg bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90">Iniciar Sesión</button>
        </div>
    </div>
    <style>
        .contenedor{
            margin-top: 10%;
            margin-left: 40%;
            width: 50%;
        }
        .input{
            color: black;
            padding: 0.45%;
            border-radius: 55px;
        }
    </style>
</body>
</html>