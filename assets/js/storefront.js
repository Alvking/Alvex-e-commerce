document.addEventListener("DOMContentLoaded", () => {
    const FALLBACK_KES_PER_USD = 130;
    const RATE_CACHE_KEY = "alvexKesPerUsdRate";
    const CURRENCY_KEY = "alvexCurrency";
    const LANGUAGE_KEY = "alvexLanguage";
    const rateCacheMaxAge = 6 * 60 * 60 * 1000;

    const translations = {
        sw: {
            "Currency": "Sarafu",
            "Language": "Lugha",
            "Home": "Nyumbani",
            "Shop": "Duka",
            "Categories": "Aina",
            "Deals": "Ofa",
            "Support": "Usaidizi",
            "Contact": "Wasiliana nasi",
            "Cart": "Kikapu",
            "Shopping Cart": "Kikapu cha Ununuzi",
            "Our Collection": "Mkusanyiko Wetu",
            "Explore Our Categories": "Gundua Aina Zetu",
            "Shop by Category": "Nunua kwa Aina",
            "Featured Collections": "Mikusanyiko Maalum",
            "View All": "Tazama Zote",
            "View Deal": "Tazama Ofa",
            "Explore Category": "Gundua Aina",
            "Explore Shop": "Tembelea Duka",
            "Explore All Products": "Gundua Bidhaa Zote",
            "Shop Now": "Nunua Sasa",
            "Shop Deals": "Nunua kwa Ofa",
            "Shop Gaming": "Nunua Vifaa vya Michezo",
            "Shop Audio": "Nunua Vifaa vya Sauti",
            "Shop Smart Home": "Nunua Vifaa Mahiri vya Nyumbani",
            "Add to Cart": "Ongeza Kikapuni",
            "Continue Shopping": "Endelea Kununua",
            "Start Shopping": "Anza Kununua",
            "Update": "Sasisha",
            "Remove": "Ondoa",
            "Clear Cart": "Futa Kikapu",
            "Proceed to Checkout": "Endelea Kulipa",
            "Back to Cart": "Rudi Kikapuni",
            "Place Order": "Weka Oda",
            "Today's Best Deals": "Ofa Bora za Leo",
            "Exclusive Deals": "Ofa Maalum",
            "SPECIAL OFFERS": "Ofa Maalum",
            "Shop All Products": "Nunua Bidhaa Zote",
            "Trending Now": "Zinazovuma Sasa",
            "Our Store": "Duka Letu",
            "Product Description": "Maelezo ya Bidhaa",
            "Quantity": "Kiasi",
            "Items": "Bidhaa",
            "Your Items": "Bidhaa Zako",
            "Order Summary": "Muhtasari wa Oda",
            "Your Order": "Oda Yako",
            "Subtotal": "Jumla Ndogo",
            "Delivery": "Uwasilishaji",
            "Delivery Fee": "Ada ya Uwasilishaji",
            "Total": "Jumla",
            "Calculated at checkout": "Hukokotolewa wakati wa malipo",
            "Delivery charges will be added during checkout.": "Gharama za uwasilishaji zitaongezwa wakati wa malipo.",
            "Review your items before proceeding to checkout.": "Kagua bidhaa zako kabla ya kuendelea kulipa.",
            "Discover products selected just for you.": "Gundua bidhaa zilizochaguliwa kwa ajili yako.",
            "Browse our collections and discover something you love.": "Vinjari mikusanyiko yetu na ugundue kitu unachokipenda.",
            "Discover amazing products at special prices.": "Gundua bidhaa nzuri kwa bei maalum.",
            "Save more on your favourite products.": "Okoa zaidi kwenye bidhaa unazopenda.",
            "No products available yet.": "Hakuna bidhaa zinazopatikana kwa sasa.",
            "No categories available": "Hakuna aina zinazopatikana",
            "No active deals available": "Hakuna ofa zinazopatikana kwa sasa",
            "Please check back later.": "Tafadhali tembelea tena baadaye.",
            "Check back later for exciting offers.": "Tembelea tena baadaye kwa ofa za kusisimua.",
            "Your Cart is Empty": "Kikapu Chako ni Tupu",
            "You haven't added any products to your shopping cart yet.": "Bado hujaongeza bidhaa kwenye kikapu chako.",
            "Add products before proceeding to checkout.": "Ongeza bidhaa kabla ya kuendelea kulipa.",
            "Secure shopping. Thank you for choosing us.": "Ununuzi salama. Asante kwa kutuchagua.",
            "Product added to your cart!": "Bidhaa imeongezwa kwenye kikapu chako!",
            "Cart updated successfully!": "Kikapu kimesasishwa!",
            "Product removed from cart.": "Bidhaa imeondolewa kwenye kikapu.",
            "Your cart has been cleared.": "Kikapu chako kimefutwa.",
            "Invalid request. Please refresh the page.": "Ombi si sahihi. Tafadhali pakia upya ukurasa.",
            "Please enter a valid quantity.": "Tafadhali weka kiasi sahihi.",
            "Requested quantity exceeds available stock.": "Kiasi ulichoomba kinazidi bidhaa zilizopo.",
            "Order Placed Successfully!": "Oda Imetumwa kwa Mafanikio!",
            "Thank you for shopping with us.": "Asante kwa kununua nasi.",
            "Your order has been recorded.": "Oda yako imerekodiwa.",
            "Order Reference": "Nambari ya Oda",
            "Order Total": "Jumla ya Oda",
            "Checkout": "Malipo",
            "Complete your delivery and payment details.": "Jaza maelezo ya uwasilishaji na malipo.",
            "Please correct the following:": "Tafadhali rekebisha yafuatayo:",
            "Customer Information": "Taarifa za Mteja",
            "Full Name": "Jina Kamili",
            "Email Address": "Anwani ya Barua Pepe",
            "Phone Number": "Nambari ya Simu",
            "Delivery Information": "Taarifa za Uwasilishaji",
            "County": "Kaunti",
            "Delivery Address": "Anwani ya Uwasilishaji",
            "Additional Delivery Notes (Optional)": "Maelezo ya Ziada ya Uwasilishaji (Si Lazima)",
            "Payment Method": "Njia ya Malipo",
            "Cash on Delivery": "Lipa Unapopokea",
            "Pay when your order is delivered.": "Lipa oda yako itakapowasilishwa.",
            "Please verify your delivery details before placing your order.": "Tafadhali hakikisha maelezo ya uwasilishaji kabla ya kuweka oda.",
            "Enter your full name": "Weka jina lako kamili",
            "Any special delivery instructions?": "Maelekezo yoyote maalum ya uwasilishaji?",
            "The STK Push payment integration is the next step.": "Muunganisho wa malipo ya STK Push utaongezwa baadaye.",
            "STK Push payment integration to follow.": "Muunganisho wa malipo ya STK Push utaongezwa baadaye.",
            "Your order is awaiting M-Pesa payment.": "Oda yako inasubiri malipo ya M-Pesa.",
            "No payment has been collected yet.": "Bado hakuna malipo yaliyopokelewa.",
            "Your order is awaiting confirmation.": "Oda yako inasubiri kuthibitishwa.",
            "Payment will be made upon delivery.": "Malipo yatafanywa bidhaa itakapowasilishwa.",
            "Don't Miss Out!": "Usikose!",
            "Explore our complete collection and discover more products.": "Gundua mkusanyiko wetu wote na upate bidhaa zaidi.",
            "No description available.": "Hakuna maelezo yanayopatikana.",
            "Invalid product or quantity.": "Bidhaa au kiasi si sahihi.",
            "Product not found.": "Bidhaa haikupatikana.",
            "Product is no longer available.": "Bidhaa hii haipatikani tena.",
            "Invalid product.": "Bidhaa si sahihi.",
            "Unable to load your cart. Please try again.": "Imeshindwa kupakia kikapu chako. Tafadhali jaribu tena.",
            "Please enter a valid email address.": "Tafadhali weka anwani sahihi ya barua pepe.",
            "Please enter your county.": "Tafadhali weka kaunti yako.",
            "Please enter your delivery address.": "Tafadhali weka anwani yako ya uwasilishaji.",
            "Delivery notes are too long.": "Maelezo ya uwasilishaji ni marefu mno.",
            "Please select a valid payment method.": "Tafadhali chagua njia sahihi ya malipo.",
            "Your shopping cart is empty.": "Kikapu chako cha ununuzi ni tupu.",
            "One or more products are no longer available.": "Bidhaa moja au zaidi hazipatikani tena.",
            "Insufficient stock for": "Bidhaa hazina akiba ya kutosha:",
            "Easy 30-Day Returns": "Marejesho Rahisi ya Siku 30",
            "Shop Smarter. Live Better.": "Nunua kwa Ujanja. Ishi Vizuri.",
            "Kenya": "Kenya",
            "Search products...": "Tafuta bidhaa...",
            "Search categories...": "Tafuta aina...",
            "Search deals...": "Tafuta ofa...",
            "Search for products...": "Tafuta bidhaa...",
            "No products found. Try another search.": "Hakuna bidhaa zilizopatikana. Jaribu kutafuta tena.",
            "Add laptop to wishlist": "Ongeza kompyuta mpakato kwenye orodha ya matamanio",
            "Add earbuds to wishlist": "Ongeza vifaa vya masikioni kwenye orodha ya matamanio",
            "Add smartphone to wishlist": "Ongeza simu mahiri kwenye orodha ya matamanio",
            "Add watch to wishlist": "Ongeza saa kwenye orodha ya matamanio",
            "Add controller to wishlist": "Ongeza kidhibiti kwenye orodha ya matamanio",
            "Add camera to wishlist": "Ongeza kamera kwenye orodha ya matamanio",
            "Your email address": "Anwani yako ya barua pepe",
            "Subscribe": "Jisajili",
            "Email address": "Anwani ya barua pepe",
            "My account": "Akaunti yangu",
            "Shopping cart": "Kikapu cha ununuzi",
            "Toggle navigation": "Fungua au funga menyu",
            "NEXT GENERATION TECH": "TEKNOLOJIA YA KIZAZI KIPYA",
            "Smarter Devices": "Vifaa Mahiri",
            "Brighter Days": "Siku Bora Zaidi",
            "Discover innovative electronics that power": "Gundua vifaa vya kielektroniki vinavyoboresha",
            "your life, work and play.": "maisha, kazi na burudani yako.",
            "Innovative": "Bunifu",
            "Technology": "Teknolojia",
            "Trusted": "Inayoaminika",
            "Quality": "Ubora",
            "Faster": "Haraka",
            "Delivery": "Uwasilishaji",
            "Tech": "Teknolojia",
            "Without": "Bila",
            "Limits": "Mipaka",
            "Laptops": "Kompyuta mpakato",
            "Smartphones": "Simu mahiri",
            "Audio": "Sauti",
            "Wearables": "Vifaa vya kuvaliwa",
            "Gaming": "Michezo",
            "Monitors": "Skrini",
            "Smart Home": "Nyumba Mahiri",
            "Pro Performance": "Utendaji wa Kitaalamu",
            "Everyday": "Kila Siku",
            "Laptops for work, play": "Kompyuta za kazi na burudani",
            "and beyond.": "na zaidi.",
            "Sound": "Sauti",
            "Reimagined": "Iliyobuniwa Upya",
            "Premium audio": "Sauti ya kiwango cha juu",
            "for a richer you.": "kwa maisha bora zaidi.",
            "Live Smarter": "Ishi kwa Ujanja",
            "at Home": "Nyumbani",
            "Smart devices for": "Vifaa mahiri kwa",
            "a connected life.": "maisha yaliyounganishwa.",
            "LIMITED TIME OFFER": "OFa YA MUDA MFUPI",
            "Up to": "Hadi",
            "Off": "Punguzo",
            "Top Tech Brands": "Chapa Bora za Teknolojia",
            "Upgrade": "Boresha",
            "Your Game": "Mchezo Wako",
            "Consoles, accessories": "Vifaa vya michezo na nyongeza",
            "and more.": "na vinginevyo.",
            "Free Shipping": "Uwasilishaji Bila Malipo",
            "On orders over": "Kwa oda zinazozidi",
            "30-Day Returns": "Marejesho ya Siku 30",
            "Hassle-free": "Bila usumbufu",
            "24/7 Support": "Usaidizi wa Saa 24",
            "Always here for you": "Tuko hapa kukusaidia",
            "Secure Payments": "Malipo Salama",
            "Shop with confidence": "Nunua kwa uhakika",
            "Join the Alvex Community": "Jiunge na Jumuiya ya Alvex",
            "Get exclusive deals, new arrivals and tech tips straight to your inbox.": "Pokea ofa maalum, bidhaa mpya na vidokezo vya teknolojia kwenye barua pepe yako.",
            "Innovative electronics for a smarter,": "Vifaa bunifu vya kielektroniki kwa maisha mahiri,",
            "brighter tomorrow.": "na kesho bora zaidi.",
            "Company": "Kampuni",
            "About Us": "Kuhusu Sisi",
            "Our Mission": "Dhamira Yetu",
            "Blog": "Blogu",
            "Careers": "Kazi",
            "Press": "Habari kwa Vyombo vya Habari",
            "Contact Us": "Wasiliana Nasi",
            "Help Center": "Kituo cha Usaidizi",
            "Shipping Info": "Taarifa za Uwasilishaji",
            "Returns & Refunds": "Marejesho na Urejeshaji wa Pesa",
            "Track Order": "Fuatilia Oda",
            "FAQs": "Maswali Yanayoulizwa Mara kwa Mara",
            "Download Our App": "Pakua Programu Yetu",
            "Get the best shopping experience on the go.": "Furahia ununuzi bora popote ulipo.",
            "App Store": "App Store",
            "Google Play": "Google Play",
            "All rights reserved.": "Haki zote zimehifadhiwa.",
            "Privacy Policy": "Sera ya Faragha",
            "Terms of Service": "Masharti ya Huduma",
            "Sitemap": "Ramani ya Tovuti",
            "Facebook": "Facebook",
            "Instagram": "Instagram",
            "YouTube": "YouTube",
            "TikTok": "TikTok",
            "Quantity:": "Kiasi:",
            "each": "kila moja",
            "Products": "Bidhaa"
        },
        fr: {
            "Currency": "Devise",
            "Language": "Langue",
            "Home": "Accueil",
            "Shop": "Boutique",
            "Categories": "Catégories",
            "Deals": "Promotions",
            "Support": "Assistance",
            "Contact": "Contact",
            "Cart": "Panier",
            "Shopping Cart": "Panier",
            "Our Collection": "Notre collection",
            "Explore Our Categories": "Découvrez nos catégories",
            "Shop by Category": "Acheter par catégorie",
            "Featured Collections": "Collections à la une",
            "View All": "Tout voir",
            "View Deal": "Voir l'offre",
            "Explore Category": "Découvrir la catégorie",
            "Explore Shop": "Découvrir la boutique",
            "Explore All Products": "Découvrir tous les produits",
            "Shop Now": "Acheter maintenant",
            "Shop Deals": "Voir les promotions",
            "Shop Gaming": "Acheter des produits gaming",
            "Shop Audio": "Acheter des produits audio",
            "Shop Smart Home": "Acheter pour la maison connectée",
            "Add to Cart": "Ajouter au panier",
            "Continue Shopping": "Continuer les achats",
            "Start Shopping": "Commencer les achats",
            "Update": "Mettre à jour",
            "Remove": "Supprimer",
            "Clear Cart": "Vider le panier",
            "Proceed to Checkout": "Passer à la commande",
            "Back to Cart": "Retour au panier",
            "Place Order": "Commander",
            "Today's Best Deals": "Les meilleures offres du jour",
            "Exclusive Deals": "Offres exclusives",
            "SPECIAL OFFERS": "OFFRES SPÉCIALES",
            "Shop All Products": "Tous les produits",
            "Trending Now": "Tendances du moment",
            "Our Store": "Notre boutique",
            "Product Description": "Description du produit",
            "Quantity": "Quantité",
            "Items": "Articles",
            "Your Items": "Vos articles",
            "Order Summary": "Récapitulatif de la commande",
            "Your Order": "Votre commande",
            "Subtotal": "Sous-total",
            "Delivery": "Livraison",
            "Delivery Fee": "Frais de livraison",
            "Total": "Total",
            "Calculated at checkout": "Calculés lors du paiement",
            "Delivery charges will be added during checkout.": "Les frais de livraison seront ajoutés lors du paiement.",
            "Review your items before proceeding to checkout.": "Vérifiez vos articles avant de passer à la commande.",
            "Discover products selected just for you.": "Découvrez des produits sélectionnés pour vous.",
            "Browse our collections and discover something you love.": "Parcourez nos collections et trouvez votre bonheur.",
            "Discover amazing products at special prices.": "Découvrez des produits exceptionnels à prix spécial.",
            "Save more on your favourite products.": "Économisez davantage sur vos produits préférés.",
            "No products available yet.": "Aucun produit disponible pour le moment.",
            "No categories available": "Aucune catégorie disponible",
            "No active deals available": "Aucune offre en cours",
            "Please check back later.": "Veuillez revenir plus tard.",
            "Check back later for exciting offers.": "Revenez bientôt pour découvrir nos offres.",
            "Your Cart is Empty": "Votre panier est vide",
            "You haven't added any products to your shopping cart yet.": "Vous n'avez encore ajouté aucun produit à votre panier.",
            "Add products before proceeding to checkout.": "Ajoutez des produits avant de passer à la commande.",
            "Secure shopping. Thank you for choosing us.": "Achats sécurisés. Merci de nous avoir choisis.",
            "Product added to your cart!": "Produit ajouté à votre panier !",
            "Cart updated successfully!": "Panier mis à jour !",
            "Product removed from cart.": "Produit retiré du panier.",
            "Your cart has been cleared.": "Votre panier a été vidé.",
            "Invalid request. Please refresh the page.": "Requête invalide. Veuillez actualiser la page.",
            "Please enter a valid quantity.": "Veuillez saisir une quantité valide.",
            "Requested quantity exceeds available stock.": "La quantité demandée dépasse le stock disponible.",
            "Order Placed Successfully!": "Commande passée avec succès !",
            "Thank you for shopping with us.": "Merci d'avoir effectué vos achats chez nous.",
            "Your order has been recorded.": "Votre commande a été enregistrée.",
            "Order Reference": "Référence de commande",
            "Order Total": "Total de la commande",
            "Checkout": "Paiement",
            "Complete your delivery and payment details.": "Complétez vos informations de livraison et de paiement.",
            "Please correct the following:": "Veuillez corriger les éléments suivants :",
            "Customer Information": "Informations client",
            "Full Name": "Nom complet",
            "Email Address": "Adresse e-mail",
            "Phone Number": "Numéro de téléphone",
            "Delivery Information": "Informations de livraison",
            "County": "Comté",
            "Delivery Address": "Adresse de livraison",
            "Additional Delivery Notes (Optional)": "Instructions de livraison supplémentaires (facultatif)",
            "Payment Method": "Mode de paiement",
            "Cash on Delivery": "Paiement à la livraison",
            "Pay when your order is delivered.": "Payez à la livraison de votre commande.",
            "Please verify your delivery details before placing your order.": "Vérifiez vos informations de livraison avant de passer commande.",
            "Enter your full name": "Saisissez votre nom complet",
            "Any special delivery instructions?": "Des instructions particulières pour la livraison ?",
            "The STK Push payment integration is the next step.": "L'intégration du paiement STK Push sera ajoutée ultérieurement.",
            "STK Push payment integration to follow.": "L'intégration du paiement STK Push sera ajoutée ultérieurement.",
            "Your order is awaiting M-Pesa payment.": "Votre commande est en attente du paiement M-Pesa.",
            "No payment has been collected yet.": "Aucun paiement n'a encore été reçu.",
            "Your order is awaiting confirmation.": "Votre commande est en attente de confirmation.",
            "Payment will be made upon delivery.": "Le paiement sera effectué à la livraison.",
            "Don't Miss Out!": "Ne manquez pas ça !",
            "Explore our complete collection and discover more products.": "Découvrez toute notre collection et trouvez encore plus de produits.",
            "No description available.": "Aucune description disponible.",
            "Invalid product or quantity.": "Produit ou quantité invalide.",
            "Product not found.": "Produit introuvable.",
            "Product is no longer available.": "Ce produit n'est plus disponible.",
            "Invalid product.": "Produit invalide.",
            "Unable to load your cart. Please try again.": "Impossible de charger votre panier. Veuillez réessayer.",
            "Please enter a valid email address.": "Veuillez saisir une adresse e-mail valide.",
            "Please enter your county.": "Veuillez saisir votre comté.",
            "Please enter your delivery address.": "Veuillez saisir votre adresse de livraison.",
            "Delivery notes are too long.": "Les instructions de livraison sont trop longues.",
            "Please select a valid payment method.": "Veuillez sélectionner un mode de paiement valide.",
            "Your shopping cart is empty.": "Votre panier est vide.",
            "One or more products are no longer available.": "Un ou plusieurs produits ne sont plus disponibles.",
            "Insufficient stock for": "Stock insuffisant pour",
            "Easy 30-Day Returns": "Retours faciles sous 30 jours",
            "Shop Smarter. Live Better.": "Achetez mieux. Vivez mieux.",
            "Kenya": "Kenya",
            "Search products...": "Rechercher des produits...",
            "Search categories...": "Rechercher des catégories...",
            "Search deals...": "Rechercher des offres...",
            "Search for products...": "Rechercher des produits...",
            "No products found. Try another search.": "Aucun produit trouvé. Essayez une autre recherche.",
            "Add laptop to wishlist": "Ajouter l'ordinateur à la liste de souhaits",
            "Add earbuds to wishlist": "Ajouter les écouteurs à la liste de souhaits",
            "Add smartphone to wishlist": "Ajouter le smartphone à la liste de souhaits",
            "Add watch to wishlist": "Ajouter la montre à la liste de souhaits",
            "Add controller to wishlist": "Ajouter la manette à la liste de souhaits",
            "Add camera to wishlist": "Ajouter la caméra à la liste de souhaits",
            "Your email address": "Votre adresse e-mail",
            "Subscribe": "S'abonner",
            "Email address": "Adresse e-mail",
            "My account": "Mon compte",
            "Shopping cart": "Panier",
            "Toggle navigation": "Afficher ou masquer la navigation",
            "NEXT GENERATION TECH": "LA TECHNOLOGIE DE DEMAIN",
            "Smarter Devices": "Des appareils plus intelligents",
            "Brighter Days": "Un quotidien meilleur",
            "Discover innovative electronics that power": "Découvrez des appareils électroniques innovants qui améliorent",
            "your life, work and play.": "votre vie, votre travail et vos loisirs.",
            "Innovative": "Innovante",
            "Technology": "Technologie",
            "Trusted": "Fiable",
            "Quality": "Qualité",
            "Faster": "Plus rapide",
            "Tech": "Technologie",
            "Without": "Sans",
            "Limits": "limites",
            "Laptops": "Ordinateurs portables",
            "Smartphones": "Smartphones",
            "Audio": "Audio",
            "Wearables": "Objets connectés",
            "Gaming": "Jeux vidéo",
            "Monitors": "Écrans",
            "Smart Home": "Maison connectée",
            "Pro Performance": "Performance professionnelle",
            "Everyday": "Au quotidien",
            "Laptops for work, play": "Des ordinateurs pour travailler, jouer",
            "and beyond.": "et bien plus.",
            "Sound": "Le son",
            "Reimagined": "réinventé",
            "Premium audio": "Un son haut de gamme",
            "for a richer you.": "pour une expérience plus riche.",
            "Live Smarter": "Vivez mieux",
            "at Home": "chez vous",
            "Smart devices for": "Des appareils intelligents pour",
            "a connected life.": "une vie connectée.",
            "LIMITED TIME OFFER": "OFFRE À DURÉE LIMITÉE",
            "Up to": "Jusqu'à",
            "Off": "de réduction",
            "Top Tech Brands": "Les meilleures marques tech",
            "Upgrade": "Améliorez",
            "Your Game": "votre expérience de jeu",
            "Consoles, accessories": "Consoles, accessoires",
            "and more.": "et bien plus.",
            "Free Shipping": "Livraison gratuite",
            "On orders over": "Pour toute commande supérieure à",
            "30-Day Returns": "Retours sous 30 jours",
            "Hassle-free": "En toute simplicité",
            "24/7 Support": "Assistance 24 h/24",
            "Always here for you": "Toujours à votre écoute",
            "Secure Payments": "Paiements sécurisés",
            "Shop with confidence": "Achetez en toute confiance",
            "Join the Alvex Community": "Rejoignez la communauté Alvex",
            "Get exclusive deals, new arrivals and tech tips straight to your inbox.": "Recevez des offres exclusives, nos nouveautés et des conseils tech par e-mail.",
            "Innovative electronics for a smarter,": "Des appareils électroniques innovants pour un avenir",
            "brighter tomorrow.": "plus intelligent et lumineux.",
            "Company": "Entreprise",
            "About Us": "À propos",
            "Our Mission": "Notre mission",
            "Blog": "Blog",
            "Careers": "Carrières",
            "Press": "Presse",
            "Contact Us": "Nous contacter",
            "Help Center": "Centre d'aide",
            "Shipping Info": "Informations de livraison",
            "Returns & Refunds": "Retours et remboursements",
            "Track Order": "Suivre une commande",
            "FAQs": "Questions fréquentes",
            "Download Our App": "Téléchargez notre application",
            "Get the best shopping experience on the go.": "Profitez d'une expérience d'achat optimale où que vous soyez.",
            "App Store": "App Store",
            "Google Play": "Google Play",
            "All rights reserved.": "Tous droits réservés.",
            "Privacy Policy": "Politique de confidentialité",
            "Terms of Service": "Conditions d'utilisation",
            "Sitemap": "Plan du site",
            "Facebook": "Facebook",
            "Instagram": "Instagram",
            "YouTube": "YouTube",
            "TikTok": "TikTok",
            "Quantity:": "Quantité :",
            "each": "l'unité",
            "Products": "Produits"
        }
    };

    const savedCurrency = localStorage.getItem(CURRENCY_KEY);
    const savedLanguage = localStorage.getItem(LANGUAGE_KEY);
    let currency = savedCurrency === "KES" ? "KES" : "USD";
    let language = ["en", "sw", "fr"].includes(savedLanguage) ? savedLanguage : "en";
    let kesPerUsd = FALLBACK_KES_PER_USD;
    let rateSource = "fallback";
    const originalTitle = document.title;
    const originalText = new WeakMap();
    const originalAttributes = new WeakMap();

    function addPreferencesBar() {
        if (document.querySelector("[data-storefront-controls]")) {
            return;
        }

        const bar = document.createElement("div");
        bar.className = "storefront-preferences-bar";
        bar.innerHTML = `
            <div class="storefront-controls" data-storefront-controls>
                <label class="storefront-control">
                    <span class="storefront-control-label">Currency</span>
                    <select data-storefront-currency aria-label="Currency">
                        <option value="USD">USD</option>
                        <option value="KES">KSh</option>
                    </select>
                </label>
                <label class="storefront-control">
                    <span class="storefront-control-label">Language</span>
                    <select data-storefront-language aria-label="Language">
                        <option value="en">English</option>
                        <option value="sw">Kiswahili</option>
                        <option value="fr">Français</option>
                    </select>
                </label>
            </div>`;
        document.body.prepend(bar);
    }

    function numberFormatter(value) {
        const locale = language === "fr" ? "fr-FR" : (language === "sw" ? "sw-KE" : "en-US");
        return new Intl.NumberFormat(locale, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    }

    function convertCurrencyText(text) {
        return text.replace(/(KSh|KES|USD|\$)\s*([\d,]+(?:\.\d{1,2})?)/gi, (match, symbol, amount) => {
            const value = Number(amount.replace(/,/g, ""));
            if (!Number.isFinite(value)) {
                return match;
            }

            const isUsd = symbol.toUpperCase() === "USD" || symbol === "$";
            let converted = value;

            if (currency === "KES" && !isUsd) {
                return match;
            }
            if (currency === "USD" && isUsd) {
                return `USD ${numberFormatter(value)}`;
            }

            converted = isUsd ? value * kesPerUsd : value / kesPerUsd;
            return currency === "KES"
                ? `KSh ${numberFormatter(converted)}`
                : `USD ${numberFormatter(converted)}`;
        });
    }

    function translateValue(value) {
        const dictionary = translations[language];
        if (!dictionary) {
            return value;
        }
        const normalized = value.replace(/\s+/g, " ").trim();
        if (dictionary[normalized]) {
            return dictionary[normalized];
        }

        const keys = Object.keys(dictionary).sort((left, right) => right.length - left.length);
        for (const key of keys) {
            const escapedKey = key.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
            const phrase = new RegExp(`(^|\\W)${escapedKey}(?=\\W|$)`);
            if (phrase.test(normalized)) {
                return normalized.replace(phrase, (match, prefix) => `${prefix}${dictionary[key]}`);
            }
        }
        return value;
    }

    function updateTextAndAttributes() {
        document.documentElement.lang = language;

        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                const parent = node.parentElement;
                if (!parent || parent.closest("script, style, noscript, textarea, select, option")) {
                    return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        });
        const nodes = [];
        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }

        nodes.forEach((node) => {
            if (!originalText.has(node)) {
                originalText.set(node, node.nodeValue);
            }

            const source = originalText.get(node);
            const leading = source.match(/^\s*/)[0];
            const trailing = source.match(/\s*$/)[0];
            const content = source.slice(leading.length, source.length - trailing.length || undefined);
            const converted = convertCurrencyText(content);
            node.nodeValue = `${leading}${translateValue(converted)}${trailing}`;
        });

        document.querySelectorAll("[placeholder], [aria-label], [title], img[alt]").forEach((element) => {
            ["placeholder", "aria-label", "title", "alt"].forEach((attribute) => {
                if (!element.hasAttribute(attribute)) {
                    return;
                }
                if (!originalAttributes.has(element)) {
                    originalAttributes.set(element, {});
                }
                const attributes = originalAttributes.get(element);
                if (!(attribute in attributes)) {
                    attributes[attribute] = element.getAttribute(attribute);
                }
                element.setAttribute(attribute, translateValue(attributes[attribute]));
            });
        });

        const titleTranslations = {
            "Alvex | Smart Electronics Store": {
                sw: "Alvex | Duka la Vifaa vya Kielektroniki Mahiri",
                fr: "Alvex | Boutique d'électronique intelligente"
            },
            "Shop | Our Store": { sw: "Duka | Duka Letu", fr: "Boutique | Notre boutique" },
            "Categories | Our Store": { sw: "Aina | Duka Letu", fr: "Catégories | Notre boutique" },
            "Special Deals | Our Store": { sw: "Ofa Maalum | Duka Letu", fr: "Offres spéciales | Notre boutique" },
            "Shopping Cart | Our Store": { sw: "Kikapu | Duka Letu", fr: "Panier | Notre boutique" },
            "Checkout | Our Store": { sw: "Malipo | Duka Letu", fr: "Paiement | Notre boutique" }
        };
        const titleTranslation = titleTranslations[originalTitle];
        document.title = titleTranslation && titleTranslation[language]
            ? titleTranslation[language]
            : originalTitle;
    }

    function syncControls() {
        document.querySelectorAll("[data-storefront-currency]").forEach((select) => {
            select.value = currency;
            select.addEventListener("change", () => {
                currency = select.value === "KES" ? "KES" : "USD";
                localStorage.setItem(CURRENCY_KEY, currency);
                document.querySelectorAll("[data-storefront-currency]").forEach((control) => {
                    control.value = currency;
                });
                updateTextAndAttributes();
            });
        });

        document.querySelectorAll("[data-storefront-language]").forEach((select) => {
            select.value = language;
            select.addEventListener("change", () => {
                language = ["en", "sw", "fr"].includes(select.value) ? select.value : "en";
                localStorage.setItem(LANGUAGE_KEY, language);
                document.querySelectorAll("[data-storefront-language]").forEach((control) => {
                    control.value = language;
                });
                updateTextAndAttributes();
            });
        });
    }

    function loadCachedRate() {
        try {
            const cached = JSON.parse(localStorage.getItem(RATE_CACHE_KEY) || "null");
            if (
                cached &&
                Number.isFinite(cached.rate) &&
                cached.rate > 0 &&
                Number.isFinite(cached.timestamp) &&
                Date.now() - cached.timestamp < rateCacheMaxAge
            ) {
                kesPerUsd = cached.rate;
                rateSource = "cached";
                return true;
            }
        } catch (error) {
            localStorage.removeItem(RATE_CACHE_KEY);
            console.warn("Could not read the cached USD/KES exchange rate.", error);
        }
        return false;
    }

    async function refreshExchangeRate() {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 7000);

        try {
            const response = await fetch("https://open.er-api.com/v6/latest/USD", {
                signal: controller.signal,
                cache: "no-store"
            });
            if (!response.ok) {
                throw new Error(`Exchange-rate service returned HTTP ${response.status}.`);
            }

            const result = await response.json();
            const rate = Number(result && result.rates && result.rates.KES);
            if (result.result !== "success" || !Number.isFinite(rate) || rate <= 0) {
                throw new Error("Exchange-rate service returned an invalid KES rate.");
            }

            kesPerUsd = rate;
            rateSource = "live";
            localStorage.setItem(RATE_CACHE_KEY, JSON.stringify({
                rate,
                timestamp: Date.now()
            }));
        } catch (error) {
            if (rateSource === "fallback") {
                kesPerUsd = FALLBACK_KES_PER_USD;
                rateSource = "fallback";
            }
            const availableRate = rateSource === "cached" ? "cached" : "fixed fallback";
            console.warn(`Could not refresh the USD/KES exchange rate; using the ${availableRate} rate.`, error);
        } finally {
            window.clearTimeout(timeout);
            updateTextAndAttributes();
        }
    }

    addPreferencesBar();
    const hasCachedRate = loadCachedRate();
    syncControls();
    updateTextAndAttributes();
    if (!hasCachedRate) {
        refreshExchangeRate();
    }
});
