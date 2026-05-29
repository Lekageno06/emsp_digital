# EMSP Digital - README Final

## Structure

- `config/` : connexion PDO et helpers securite.
- `includes/` : header, navbar, footer reutilisables.
- `auth/` : connexion, deconnexion, profil.
- `admin/` : dashboard et CRUD utilisateurs.
- `enseignant/` : cours, seances, evaluations, notes.
- `etudiant/` : cours, inscriptions, evaluations.
- `api/` : proxy OpenAI securise.
- `assets/` : scripts JS et correctifs CSS.
- `tests/` : audit statique securite.

## Installation

1. Importer `emsp_digital.sql` dans MySQL.
2. Importer `seed_admin.sql`.
3. Configurer `config/dbcon.php` si les identifiants MySQL changent.
4. Pour l'assistant IA OpenAI, modifier `config/openai.local.php` ou definir cote serveur :

```text
OPENAI_API_KEY=ta_cle_openai
OPENAI_MODEL=gpt-4.1-mini
```

5. Ouvrir `http://localhost/emsp-digital/`.

Compte initial :

```text
Email : admin@emsp.local
Mot de passe : Admin@123
```

## Commandes De Test

```powershell
Get-ChildItem -Path . -Recurse -Filter *.php | ForEach-Object { & 'C:\wamp64\bin\php\php8.3.28\php.exe' -n -l $_.FullName }
& 'C:\wamp64\bin\php\php8.3.28\php.exe' -n tests\verify_security.php
```

## Checklist Soutenance

- [ ] Connexion PDO avec `ERRMODE_EXCEPTION`.
- [ ] Sessions securisees et redirection par role.
- [ ] CRUD utilisateurs avec modales Bootstrap et DataTables FR.
- [ ] Cours, inscriptions, evaluations et notes avec requetes preparees.
- [ ] `password_hash()` et `password_verify()` utilises.
- [ ] Aucun `mysqli`, `md5()` ou `sha1()`.
- [ ] Widget IA OpenAI sans cle exposee cote client.
- [ ] Consentement IA et suppression historique RG-31.
- [ ] Commentaires d'alignement TP dans les fichiers critiques.
