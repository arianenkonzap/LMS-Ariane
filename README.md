# LMS-ARIANE

Plateforme LMS (Learning Management System) développée en **HTML / CSS / JavaScript / AJAX / PHP / MySQL**.

> *« Suivez le fil de votre apprentissage »* — le **fil d'Ariane** est le motif visuel central de la plateforme : il représente le chemin de progression d'un étudiant à travers les leçons d'un module.

---

## 1. Rôles et fonctionnalités

### 👩‍🏫 Enseignant
- Crée des **cours** rattachés à un module défini par le promoteur.
- Ajoute des **leçons** (document PDF ou vidéo) à chaque cours, avec ordre séquentiel.
- Crée une **évaluation (QCM)** à la fin de chaque leçon (questions, propositions, bonne réponse, points).

### 🎓 Étudiant
- Parcourt les **modules** et suit les leçons sous forme de **chemin** (le « fil »).
- Visualise le PDF ou la vidéo de chaque leçon.
- Passe l'évaluation associée (corrigée instantanément en **AJAX**).
- Voit sa **progression en %** par module, calculée à partir des notes obtenues.
- Reçoit automatiquement un **certificat** lorsque toutes les leçons d'un module sont
  complétées avec une moyenne ≥ au seuil défini par le promoteur.

### 🧭 Promoteur
- Crée et gère les **modules** (titre, description, seuil de validation en %).
- Consulte la liste des **étudiants** inscrits.
- Consulte les **certificats** délivrés (étudiant, module, score, code unique).

---

## 2. Architecture des fichiers

```
LMS-ARIANE/
├── config/
│   └── database.php          # Connexion PDO MySQL
├── includes/
│   ├── auth.php               # Session, contrôle des rôles
│   ├── header.php / footer.php
│   └── sidebar.php             # Navigation par rôle
├── assets/
│   ├── css/style.css           # Design system "fil d'Ariane"
│   └── js/
│       ├── quiz.js             # Soumission AJAX des évaluations
│       └── quiz-builder.js     # Ajout dynamique de questions (enseignant)
├── auth/
│   ├── login.php / register.php / logout.php
├── promoteur/
│   ├── dashboard.php / modules.php / etudiants.php
├── enseignant/
│   ├── dashboard.php / cours.php / lecons.php
├── etudiant/
│   ├── dashboard.php / modules.php / lecon.php / certificats.php
│   ├── ajax_evaluation.php     # Correction AJAX du QCM
│   └── ajax_terminer.php       # Validation leçon sans QCM
├── uploads/
│   ├── pdf/ · video/ · certificates/
├── database/
│   └── lms_ariane.sql          # Script SQL complet + données de démo
└── index.php                   # Redirection selon session
```

---

## 3. Installation (XAMPP / WAMP / MAMP)

1. Copiez le dossier `LMS-ARIANE` dans `htdocs/` (XAMPP) ou `www/` (WAMP).
2. Démarrez **Apache** et **MySQL**.
3. Ouvrez **phpMyAdmin**, créez une base via l'import du fichier :
   ```
   database/lms_ariane.sql
   ```
   (ce script crée la base `lms_ariane`, toutes les tables, et insère des données de démo).
4. Vérifiez les identifiants dans `config/database.php` (par défaut `root` / mot de passe vide).
5. Donnez les droits d'écriture au dossier `uploads/` :
   ```
   chmod -R 777 uploads/
   ```
6. Accédez à `http://localhost/LMS-ARIANE/`.

### Comptes de démonstration
| Rôle       | Email                  | Mot de passe   |
|------------|------------------------|----------------|
| Promoteur  | promoteur@ariane.cm    | password123    |
| Enseignant | enseignant@ariane.cm   | password123    |
| Étudiant   | etudiant@ariane.cm     | password123    |

---

## 4. Parcours type de démonstration

1. **Promoteur** : créer un module → définir le seuil de validation (ex. 60 %).
2. **Enseignant** : créer un cours dans ce module → ajouter des leçons (PDF/vidéo) →
   créer une évaluation (QCM) pour chaque leçon.
3. **Étudiant** : ouvrir le module → suivre la leçon 1 (chemin "fil") → répondre au quiz
   (correction AJAX instantanée) → la leçon suivante se débloque → une fois toutes les
   leçons terminées avec une moyenne suffisante, le **certificat** est généré
   automatiquement et imprimable en PDF.

---

## 5. Notes techniques

- Mots de passe hashés avec `password_hash()` / `password_verify()`.
- Sessions PHP classiques (`$_SESSION`) pour l'authentification et le contrôle d'accès par rôle.
- Toutes les requêtes SQL utilisent des requêtes préparées **PDO** (protection injection SQL).
- La progression et les certificats sont calculés et attribués **automatiquement** côté serveur.
- Design : palette "fil d'Ariane" (encre, papier crème, fil doré, violet labyrinthe),
  typographies Fraunces / Manrope / JetBrains Mono.
