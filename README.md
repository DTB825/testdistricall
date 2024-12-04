Ce projet est une API RESTful développée avec Symfony pour gérer des tâches. Elle permet de réaliser les opérations suivantes :
- **Lister et paginer les tâches** : Les tâches sont paginées par 10 par défaut.
- **Rechercher des tâches** : Recherche par titre ou description.
- **Créer, mettre à jour et supprimer des tâches**.
- **Valider les données** : Le titre doit comporter entre 3 et 255 caractères, la description ne doit pas être vide, et le statut est limité à `todo`, `in_progress` ou `done`.

Instructions pour démarrer le serveur :symfony server:start
Instructions pour exécuter les migrations :php bin/console doctrine:migrations:migrate
