# NTS Vault — Parcours utilisateur (étape par étape, pour implémentation)

## Étape 1 — Clic sur "NTS Vault" dans la navigation

Route : `GET /vault`

Logique côté contrôleur :
- Si l'utilisateur est connecté ET a accès au Vault (`user->hasVaultAccess()`, voir spec de tarification) → redirection directe vers `/vault/bankrolls` (étape 2). Pas de détour par la page marketing : il paie déjà, on ne lui revend rien.
- Sinon (visiteur non connecté, ou connecté mais sans accès Vault) → affiche la **page de présentation** déjà maquettée (hero, "Votre bankroll, notre priorité", icônes de garanties, etc.), avec en CTA final :
  - Si non connecté → "Découvrir nos offres" (vers `/offres`) et "Se connecter"
  - Si connecté mais sans accès → "Découvrir nos offres" (vers `/offres`) directement

## Étape 2 — "Mes bankrolls" (liste)

Route : `GET /vault/bankrolls`

Une carte par bankroll créé, avec un minimum d'informations pour rester lisible d'un coup d'œil :
- Nom du bankroll (ex. "Foot Ligue 1", "Stratégie value bet")
- Date de création
- Gain total (€ ou unités)
- ROI (%)

Actions sur chaque carte : **Modifier** (nom), **Supprimer** (avec confirmation). Clic sur la carte elle-même → étape 3 (détail).

Bouton "+ Nouveau bankroll" en haut de la liste, désactivé/grisé avec message si la limite du palier est atteinte (1 / 5 / 10 / illimité selon abonnement — voir spec tarification), avec lien vers `/offres` pour upgrader.

Routes associées :
- `POST /vault/bankrolls/nouveau` — création (vérifie `maxBankrolls` côté serveur avant d'autoriser)
- `POST /vault/bankrolls/{id}/modifier` — renommage
- `POST /vault/bankrolls/{id}/supprimer` — suppression (confirmation requise)

## Étape 3 — Détail d'un bankroll (clic sur une carte)

Route : `GET /vault/bankrolls/{id}`

Ici on retrouve la vue complète déjà maquettée : solde actuel, mise totale, gain total, ROI, évolution (graphique), taux de réussite, historique des transactions, formulaire "ajouter un pari à ce bankroll", objectifs. Toutes les données affichées ici sont scopées à CE bankroll précis (pas mélangées avec les autres bankrolls de l'utilisateur).

---

**Note pour Claude Code** : la maquette de la page de présentation Vault et de la vue détail existent déjà dans les fichiers de design fournis — ce document sert à préciser la logique de routage et le contenu exact de la liste (étape 2), qui n'était pas encore détaillée.

