<?php
// repair_database.php
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

try {
    (new Dotenv())->bootEnv('.env');
    
    $kernel = new App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $connection = $container->get('doctrine.dbal.default_connection');
    
    echo "🔧 Réparation de la base de données...\n\n";
    
    // Essayez de supprimer la clé étrangère si elle existe
    try {
        $connection->executeQuery('ALTER TABLE account DROP FOREIGN KEY IF EXISTS account_ibfk_1');
        echo "✅ Clé étrangère account_ibfk_1 supprimée (si elle existait)\n";
    } catch (\Exception $e) {
        echo "⚠️  Erreur lors de la suppression de la clé: " . $e->getMessage() . "\n";
    }
    
    // Mettez à jour le schéma
    echo "\n🔄 Mise à jour du schéma...\n";
    exec('php bin/console doctrine:schema:update --force --complete 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✅ Schéma mis à jour avec succès!\n";
    } else {
        echo "❌ Erreur lors de la mise à jour:\n";
        echo implode("\n", $output) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}