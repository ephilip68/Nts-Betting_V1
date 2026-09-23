# Connexion Google / Apple

Le code est prêt et testé (redirection réelle vérifiée vers Google et Apple). Il ne manque
que tes identifiants — tant qu'ils ne sont pas renseignés, les boutons "Continuer avec
Google/Apple" redirigeront vers Google/Apple qui refuseront la connexion (client_id vide).

## Google

1. Va sur https://console.cloud.google.com/apis/credentials
2. Crée des identifiants OAuth 2.0 (type "Application web")
3. Ajoute comme URI de redirection autorisée :
   `http://ton-domaine/connect/google/check` (remplace par ton vrai domaine en prod)
4. Copie le Client ID et le Client Secret dans `.env.local` :
   ```
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   ```
5. C'est tout — teste en cliquant sur "Continuer avec Google" sur `/login`.

## Apple

Plus impliqué : nécessite un compte Apple Developer payant (99$/an), et le secret n'est pas
une valeur fixe (c'est un JWT signé, à régénérer tous les 6 mois maximum).

1. Sur https://developer.apple.com/account/resources/identifiers, crée :
   - un **App ID** (si tu n'en as pas déjà un pour l'app)
   - une **Services ID** → c'est ton `APPLE_CLIENT_ID`. Configure "Sign in with Apple" dessus
     avec comme URI de redirection : `http://ton-domaine/connect/apple/check`
   - une **clé privée** ("Keys" > "+", cocher "Sign in with Apple") → télécharge le fichier
     `.p8` (impossible à re-télécharger ensuite, garde-le précieusement)
2. Récupère ton **Team ID** (en haut à droite du portail développeur) et le **Key ID** de la
   clé créée à l'étape précédente.
3. Renseigne dans `.env.local` :
   ```
   APPLE_CLIENT_ID=ton.services.id
   APPLE_TEAM_ID=...
   APPLE_KEY_ID=...
   APPLE_PRIVATE_KEY_PATH=/chemin/absolu/vers/AuthKey_XXXX.p8
   ```
4. Génère le secret : `php bin/console app:apple:generate-client-secret`, puis colle le
   résultat dans `.env.local` :
   ```
   APPLE_CLIENT_SECRET=<résultat de la commande>
   ```
5. Teste en cliquant sur "Continuer avec Apple" sur `/login`.
6. **Relance la commande et remets à jour `APPLE_CLIENT_SECRET` avant l'expiration** (max 6
   mois) — sinon la connexion Apple cessera de fonctionner silencieusement.

## Comment ça marche

- Première connexion : un compte `User` est créé automatiquement (email + pseudo dérivés du
  profil Google/Apple), avec un mot de passe aléatoire inutilisable (l'utilisateur ne se
  connecte jamais par mot de passe) et `isVerified = true` (l'e-mail est déjà vérifié par
  Google/Apple).
- Connexions suivantes : reconnu via `googleId`/`appleId`, ou par e-mail si le compte existait
  déjà (ex : inscrit par e-mail classique, puis connexion Google avec la même adresse plus tard).
- Code : `src/Controller/OAuthController.php`, config : `config/packages/knpu_oauth2_client.yaml`.
