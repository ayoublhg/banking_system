<?php
// Test simple pour vérifier si le dossier d'upload est accessible
echo "<h1>Test Upload Configuration</h1>";

$uploadDir = __DIR__ . '/images/services';
echo "<p>Upload directory: $uploadDir</p>";

if (is_dir($uploadDir)) {
    echo "<p style='color:green;'>✅ Dossier d'upload existe</p>";
    
    if (is_writable($uploadDir)) {
        echo "<p style='color:green;'>✅ Dossier d'upload est accessible en écriture</p>";
    } else {
        echo "<p style='color:red;'>❌ Dossier d'upload n'est pas accessible en écriture</p>";
        echo "<p>Permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Dossier d'upload n'existe pas</p>";
}

echo "<h2>Test VichUploader Configuration</h2>";
echo "<p>URL prefix: /images/services</p>";
echo "<p>Exemple d'URL d'image: http://" . $_SERVER['HTTP_HOST'] . "/images/services/filename.jpg</p>";

// Test d'upload simple
if ($_FILES) {
    $targetFile = $uploadDir . '/' . basename($_FILES["testFile"]["name"]);
    if (move_uploaded_file($_FILES["testFile"]["tmp_name"], $targetFile)) {
        echo "<p style='color:green;'>✅ Upload test réussi!</p>";
        echo "<p>Fichier: " . basename($_FILES["testFile"]["name"]) . "</p>";
        echo "<p>Taille: " . $_FILES["testFile"]["size"] . " bytes</p>";
    }
}
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="testFile">
    <input type="submit" value="Tester l'upload">
</form>