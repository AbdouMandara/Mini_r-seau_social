Utilise un design moderne pour un mini réseau social sérieux dont la couleur pricipal est le bleu de facebook, utilise de belles polices et une police unique pour représenter le logo

## 1. Vision globale du projet

**Nom du projet :** `!Pozterr`
**Type :** Mini réseau social (posts texte + image optionnelle)
**Architecture :**

* **Frontend** : Vue.js (SPA)
* **Backend** : Laravel (API REST)
* **Communication** : JSON via API sécurisée
* **Design system** : couleur principale = **bleu Facebook**

---

## 2. Architecture fonctionnelle

L’application est divisée en **2 espaces distincts** :

### 🔹 Espace User

* Authentification
* Gestion des posts
* Likes & commentaires
* Notifications
* Profil utilisateur

### 🔹 Espace Admin

* Supervision des utilisateurs
* Modération des posts & commentaires
  *(Tu pourras le développer après, bonne décision de commencer par user)*

---

## 3. Authentification & inscription (User)

### Pages

* **Page par défaut** : `/login`
* **Inscription** : `/register`

### Champs demandés

* **Nom d’utilisateur**

  * Unique
  * Obligatoire
* **Mot de passe**

  * Minimum 8 caractères
  * Hashé côté backend
* **Photo de profil**

  * Upload obligatoire
  * Input file custom :

    * Icône appareil photo
    * Label clair “Image de profil”
* **Ville**

  * Select contenant les **10 régions du Cameroun**
  * Select stylisé (custom `<option>`, moderne)

### Validation

* **Backend = source de vérité**
* Tous les champs obligatoires
* En cas d’erreur :

  * Bordure rouge sur l’input
  * Message d’erreur clair
  * Gestion propre côté Vue

👉 **Très bon réflexe : validation front + back**

---

## 4. Redirections après authentification

Après connexion réussie :

```
/{nom_user}/home
```

Contenu :

* Tous les posts de l’application (feed global)

---

## 5. Gestion des posts

### ➕ Création de post

**URL** : `/{nom_user}/add_post`

Champs :

* Image du post (optionnelle)
* Description (obligatoire)

  * Limite : **100 caractères**
  * Contrôlée front + back
* Autoriser les commentaires

  * Toggle ON/OFF
  * Par défaut : **ON**

Après création :

* Redirection vers `/home`
* Message de confirmation :

  * En haut de l’écran
  * Animation fluide
  * Durée max : **2.5s**
  * Background vert

---

### ✏️ Modification de post

**URL** : `/{nom_user}/update_post/{id_post}`

* Tous les champs sont modifiables
* Redirection vers `/profil`
* Message de confirmation :

  * Animation identique
  * Background bleu

---

### 🗑️ Suppression de post

**Type** : Soft delete
**URL** : `/{nom_user}/delete_post/{id_post}`

* `is_delete = true`
* Redirection vers `/profil`
* Message de confirmation propre

---

### Sécurité & accès

* Middleware obligatoire :

  * User authentifié
  * Bon utilisateur
  * Droits valides

---

## 6. Composants Vue.js (bonne pratique)

👉 Excellente idée ici.

* **FormComponent**

  * Utilisé pour add / update post
  * Props dynamiques :

    * titre
    * bouton
    * valeurs initiales
* **PostCardComponent**

  * Réutilisable partout

---

## 7. Affichage d’un post (PostCard)

### Header

* Photo de profil du user
* Nom du user
* Texte : `rejoint le {date_creation_compte}`

### Body

* Image du post (si existe)
* Description du post

### Footer

* ❤️ Like

  * Icône cœur
  * Nombre de likes
  * Au clic :

    * Incrément en BDD
    * Icône devient rouge
    * 🎉 Confettis à l’écran
* 💬 Commentaires

  * Icône active ou désactivée selon autorisation
* 🔗 Partage

  * WhatsApp, etc.
  * Lien :

    ```
    /{nom_user}/post/{id_post}
    ```

---

## 8. Commentaires

* Modal ou drawer
* En haut :

  * Input “Ajouter un commentaire”
  * Bouton ajouter
* En dessous :

  * Liste des commentaires
  * Sinon :

    * Message “Aucun commentaire pour l’instant”
    * Icône illustrative

### Actions sur commentaire

* Ajouter
* Modifier
* Supprimer
* Menu `⋮` à droite
* **Toutes les actions validées côté backend**

---

## 9. Page Home – Responsive

### 📱 Mobile

* **Header**

  * `!Pozterr`
  * Icône cloche (notifications)
* **Body**

  * Tous les posts
* **Footer (grid 3 éléments)**

  * 🏠 Home → `/home`
  * ➕ Add post → `/add_post`
  * 👤 Photo profil → `/profil`

### 🖥️ Desktop

* Header :

  * Nom de l’app
  * Navbar :

    * CTA “Ajouter un post” (bleu principal)
    * Photo de profil (profil)
* Body :

  * Posts en grid
  * 2 colonnes par post

---

## 10. Page Profil

### User

* Inspiration **TikTok**
* Agencement vertical fluide
* Focus sur :

  * Posts du user
  * Interactions
  * Responsive mobile / desktop

### Admin

* À concevoir plus tard (bonne priorisation)

---

## 11. Backend Laravel – Structure propre

### Routes

* Nommées
* Méthodes HTTP respectées
* Protégées (auth + middleware)

### Controllers

* UserController
* PostController
* LikeController
* CommentController

---

## 12. Base de données (Eloquent ORM)

### Tables

**users**

* id
* nom
* photo_profil
* password
* created_at

**posts**

* id_post
* img_post
* description
* is_delete (default false)
* id_user
* created_at / updated_at

**likes**

* id_like
* id_user
* id_post

**commentaires**

* id_commentaire
* id_user
* id_post
* contenu
* date_commentaire

---

## 13. Models

### User

```php
$fillable = ['nom', 'photo_profil', 'password'];
```

### Post

```php
$fillable = ['img_post', 'description'];
```

* Création via `Post::create()`

---

## 14. Gestion des images

### Validation image post

```
nullable | image | mimes:jpeg,jpg,png,svg | max:2048
```

### Stockage

* Post :

  ```
  /public/images/post
  ```

* Photo profil :

  ```
  /public/images/profil_user
  ```

* Nom du fichier :

  ```
  date_heure.extension
  ```

---

## 15. Données de test

* Factories :

  * UserFactory
  * PostFactory
* Seeders pour peupler la BDD

---

## Orientation claire (conseil senior)

👉 **Commence dans cet ordre :**

1. Auth API (Laravel Sanctum)
2. CRUD Post
3. Like
4. Commentaire
5. Notifications
6. UI / animations
