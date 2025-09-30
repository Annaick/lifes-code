# Fonctionnalité Livraison vers un Magasin - Documentation

## Vue d'ensemble

Cette fonctionnalité vous permet d'assigner des méthodes de livraison WooCommerce à des inventaires de magasins spécifiques. Lorsqu'un client sélectionne une méthode de livraison assignée à un magasin, le système va :

1. **Valider la disponibilité du stock** - N'afficher la méthode de livraison que si tous les produits ont un stock suffisant dans le magasin assigné
2. **Déduire le stock du bon magasin** - Lorsqu'une commande est passée, le stock est déduit de l'inventaire du magasin assigné
3. **Restaurer le stock au bon magasin** - Lorsqu'une commande est annulée ou remboursée, le stock est restauré dans le magasin correspondant

## Comment ça fonctionne

### 1. Système Multi-Magasin & Multi-Inventaire

Le plugin prend en charge :
- **Plusieurs magasins** - Chaque magasin peut être configuré indépendamment
- **Multi-inventaire par produit** - Chaque produit peut avoir des niveaux de stock séparés pour chaque magasin
- **Fonctionnalité Pack/Lot** - Les produits peuvent être configurés pour déduire le stock d'autres produits composants

### 2. Attribution d'une Méthode de Livraison

#### Instructions de Configuration

1. Naviguez vers **WooCommerce → Réglages → Livraison**
2. Sélectionnez une zone de livraison
3. Cliquez sur une méthode de livraison (ex. "Tarif fixe", "Retrait sur place", "Livraison gratuite")
4. Vous verrez un nouveau champ : **"Magasin Assigné"**
5. Sélectionnez le magasin que vous souhaitez assigner à cette méthode de livraison
6. Enregistrez les modifications

#### Méthodes de Livraison Supportées

La fonctionnalité fonctionne avec :
- Tarif fixe (Flat Rate)
- Livraison gratuite (Free Shipping)
- Retrait sur place (Local Pickup)
- Toute autre méthode de livraison standard WooCommerce

### 3. Validation du Stock lors du Passage en Caisse

Lorsqu'un client est sur la page de paiement :

1. Le système vérifie tous les produits dans le panier
2. Pour chaque méthode de livraison avec un magasin assigné :
   - Si un produit a le **multi-stock activé** : Vérifier si le magasin assigné a un stock suffisant
   - Si le stock est insuffisant : Masquer la méthode de livraison
   - Si le stock est suffisant : Afficher la méthode de livraison
3. Si un produit n'a pas le multi-stock activé, la méthode de livraison est disponible (utilise le stock général)

### 4. Déduction du Stock à la Finalisation de la Commande

Lorsqu'une commande est passée avec une méthode de livraison assignée à un magasin :

1. Le système enregistre l'ID du magasin assigné à la méthode de livraison dans les métadonnées de commande : `_yith_pos_shipping_store`
2. Le stock est déduit de l'inventaire du magasin assigné (pas du stock par défaut/général)
3. Les articles de commande sont marqués avec des métadonnées pour suivre d'où le stock a été réduit :
   - `_reduced_stock` - Quantité totale réduite
   - `_yith_pos_reduced_stock_by_store` - ID du magasin d'où le stock a été réduit
   - `_yith_pos_reduced_stock_by_store_qty` - Quantité réduite du magasin

### 5. Restauration du Stock

Lorsqu'une commande est annulée, remboursée ou restaurée :

1. Le système vérifie les métadonnées de commande `_yith_pos_shipping_store`
2. Si présent, le stock est restauré dans l'inventaire du bon magasin
3. La restauration suit la même logique que la réduction, garantissant la cohérence

## Détails Techniques

### Fichiers Clés

- **`includes/class.yith-pos-shipping-to-store.php`** - Classe principale de la fonctionnalité
- **`includes/class.yith-pos.php`** - Mis à jour pour charger la nouvelle classe
- **`includes/class.yith-pos-stock-management.php`** - Gestionnaire multi-stock existant (utilisé par la fonctionnalité)

### Hooks Utilisés

#### Filtres
- `woocommerce_shipping_instance_form_fields_flat_rate` - Ajoute le champ magasin aux paramètres de tarif fixe
- `woocommerce_shipping_instance_form_fields_free_shipping` - Ajoute le champ magasin aux paramètres de livraison gratuite
- `woocommerce_shipping_instance_form_fields_local_pickup` - Ajoute le champ magasin aux paramètres de retrait sur place
- `woocommerce_package_rates` - Filtre les méthodes de livraison disponibles en fonction du stock
- `woocommerce_can_reduce_order_stock` - Gère la réduction personnalisée du stock
- `woocommerce_can_restore_order_stock` - Gère la restauration personnalisée du stock
- `woocommerce_can_restock_refunded_items` - Gère la restauration du stock lors du remboursement

#### Actions
- `woocommerce_checkout_order_processed` - Enregistre les informations du magasin de la méthode de livraison dans les métadonnées de commande

### Schéma de Base de Données

#### Métadonnées de Méthode de Livraison
- `_yith_pos_assigned_store_id` - Stocke l'ID du magasin assigné pour chaque instance de méthode de livraison

#### Métadonnées de Commande
- `_yith_pos_shipping_store` - Stocke quel inventaire de magasin doit être utilisé pour cette commande

#### Métadonnées d'Article de Commande (par ligne)
- `_reduced_stock` - Quantité totale de stock réduite
- `_yith_pos_reduced_stock_by_store` - ID du magasin d'où le stock a été réduit
- `_yith_pos_reduced_stock_by_store_qty` - Quantité réduite du magasin
- `_yith_pos_reduced_stock_by_general` - Quantité réduite du stock général (solution de repli)

## Exemples de Cas d'Usage

### Cas d'Usage 1 : Click and Collect pour une Boutique Spécifique

**Configuration :**
1. Créer une méthode de livraison : "Click and Collect : Boutique A"
2. L'assigner au magasin "Boutique A"
3. Le client ajoute des produits au panier
4. Lors du paiement, "Click and Collect : Boutique A" n'apparaît que si la Boutique A a suffisamment de stock
5. Commande passée → Stock déduit de l'inventaire de la Boutique A

### Cas d'Usage 2 : Plusieurs Emplacements de Magasin

**Configuration :**
1. Créer une méthode de livraison : "Retrait sur place : Magasin Centre-ville" → Assigner au Magasin Centre-ville
2. Créer une méthode de livraison : "Retrait sur place : Magasin Centre Commercial" → Assigner au Magasin Centre Commercial
3. Les clients ne voient que les options de retrait où le stock est disponible
4. Chaque commande déduit de l'inventaire du bon magasin

### Cas d'Usage 3 : Inventaire Mixte

**Configuration du Produit :**
- Produit A : Multi-stock activé avec du stock dans le Magasin 1 et le Magasin 2
- Produit B : Multi-stock NON activé (utilise uniquement le stock général)

**Résultat :**
- Les méthodes de livraison assignées au Magasin 1 ou au Magasin 2 seront disponibles
- Stock du Produit A vérifié dans le magasin assigné
- Produit B utilise le stock général (non validé par magasin)

## Notes Importantes

### Compatibilité avec les Commandes POS

- Cette fonctionnalité affecte uniquement les **commandes du site web** (paiement WooCommerce)
- Les **commandes POS** sont gérées par le système de gestion de stock POS existant
- Les commandes POS sont automatiquement détectées et ignorées par cette fonctionnalité

### Compatibilité avec la Fonctionnalité Pack/Lot

- La fonctionnalité pack/lot continue de fonctionner comme prévu
- Les produits composants sont suivis séparément
- La déduction de stock se produit à la fois pour le produit pack et ses composants

### Comportement de Repli

Si un produit avec multi-stock activé n'a pas de stock défini pour le magasin assigné :
- La méthode de livraison sera masquée (non disponible)
- Cela empêche que des commandes soient passées lorsque le stock n'est pas disponible

Si un produit n'a pas le multi-stock activé :
- La méthode de livraison reste disponible
- Le stock général/par défaut est utilisé

## Liste de Vérification pour les Tests

- [ ] Configurer un produit avec multi-stock pour plusieurs magasins
- [ ] Créer une méthode de livraison et l'assigner à un magasin spécifique
- [ ] Ajouter un produit au panier et vérifier que la méthode de livraison n'apparaît que lorsque le magasin a du stock
- [ ] Passer une commande et vérifier que le stock est déduit du bon magasin
- [ ] Annuler/rembourser la commande et vérifier que le stock est restauré dans le bon magasin
- [ ] Tester avec des produits qui n'ont pas le multi-stock activé
- [ ] Tester avec des produits pack/lot
- [ ] Vérifier que les commandes POS ne sont pas affectées

## Dépannage

### La méthode de livraison ne s'affiche pas

**Causes possibles :**
1. Le magasin assigné n'a pas suffisamment de stock
2. Le multi-stock n'est pas activé sur le produit
3. Le multi-stock est activé mais aucun stock n'est défini pour le magasin assigné

**Solution :**
- Vérifier les paramètres d'inventaire du produit
- Vérifier que le multi-stock est activé et configuré pour le magasin
- S'assurer que la quantité de stock est suffisante pour les articles du panier

### Le stock ne se déduit pas du bon magasin

**Causes possibles :**
1. La méthode de livraison n'a pas de magasin assigné
2. La gestion de stock WooCommerce est désactivée

**Solution :**
- Aller dans les paramètres de la méthode de livraison et vérifier l'assignation du magasin
- S'assurer que WooCommerce → Réglages → Produits → Inventaire → "Gérer le stock" est activé
- Vérifier les métadonnées de commande pour `_yith_pos_shipping_store` pour confirmer que le magasin a été enregistré

### Problèmes de restauration du stock

**Causes possibles :**
1. La commande a été créée en tant que commande POS (gérée différemment)
2. La réduction de stock n'a pas été correctement suivie

**Solution :**
- Vérifier les métadonnées d'article de commande pour `_yith_pos_reduced_stock_by_store` ou `_yith_pos_reduced_stock_by_general`
- Vérifier que la commande a les métadonnées `_yith_pos_shipping_store`

## Support

Pour les problèmes ou questions, vérifier :
1. Les journaux WooCommerce (WooCommerce → État → Journaux)
2. Les notes de commande (chaque commande affiche les notes de réduction/restauration du stock)
3. Les métadonnées de produit dans la base de données

## Version

- **Version de la fonctionnalité :** 1.0.0
- **Compatible avec :** Plugin Lifes-code POS v1.0.0+
- **Nécessite :** WooCommerce 9.6+
