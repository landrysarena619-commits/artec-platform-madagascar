import random
from datetime import datetime, timedelta

import mysql.connector
from config import DB_CONFIG

COMPTES = {
    1: {"nom": "Orange Madagascar", "facebook": "OrangeMadagascar", "instagram": "orange_madagascar", "tiktok": "orange_madagascar"},
    2: {"nom": "Yas Madagascar", "facebook": "YasMadagascar", "instagram": "yas_madagascar", "tiktok": "yas_madagascar"},
    3: {"nom": "Airtel Madagascar", "facebook": "AirtelMadagascar", "instagram": "airtel_madagascar", "tiktok": "airtel_madagascar"}
}

TEXTES_FACEBOOK = [
    "Decouvrez notre nouvelle offre fibre optique a prix reduit. Installation gratuite pour toute souscription ce mois-ci.",
    "Orange Madagascar s'engage pour la couverture nationale. Nouvelle antenne inauguree dans la region Boeny.",
    "Profitez de nos forfaits mobiles avec appels illimites et data generee. Offre valable jusqu'a la fin du mois.",
    "Notre service client est disponible 24h/24 et 7j/7 pour repondre a toutes vos questions.",
    "Orange Money lance les transferts gratuits entre particuliers pour toute transaction inferieure a 50000 Ar.",
    "Retour en images sur notre action citoyenne dans les ecoles d'Antananarivo.",
    "Nouvelle promotion sur les smartphones 4G. Jusqu'a 30 pourcent de reduction.",
    "Nous investissons dans la formation des jeunes aux metiers du numerique a Madagascar.",
    "Le reseau Orange est desormais disponible dans 15 nouvelles communes rurales.",
    "Merci a nos 5 millions de clients pour leur confiance renouvelee."
]

TEXTES_INSTAGRAM = [
    "Nouvelle offre Fibre a 100 Mbps. Debit garanti, installation rapide. #OrangeMadagascar #Fibre",
    "Notre equipe technique sur le terrain pour ameliorer la qualite du reseau. #Reseau #Qualite",
    "Concours photo ouvert a tous. Gagnez un smartphone 4G. #Concours #Madagascar",
    "Zoom sur notre agence d'Analakely, entierement renovee pour mieux vous accueillir.",
    "Orange Money, c'est aussi le paiement de vos factures en ligne. #OrangeMoney",
    "Nos techniciens formes aux nouvelles technologies 5G. #Innovation #5G",
    "Retour sur le lancement de notre nouvelle gamme de forfaits. #Nouveaute",
    "Journee portes ouvertes dans nos agences. Venez nombreux.",
    "Nos engagements RSE pour un numerique responsable a Madagascar.",
    "Merci pour vos 100 000 abonnes sur cette page."
]

TEXTES_TIKTOK = [
    "Tutoriel : comment activer votre forfait en 3 clics #astuce #orange",
    "Dans les coulisses de notre centre technique #behindthescenes",
    "3 raisons de choisir la fibre Orange #fibre #madagascar",
    "Reponse a un client mecontent #serviceclient",
    "Notre nouvelle pub TV en avant-premiere #pub",
    "Comment recharger votre credit en 10 secondes #tutoriel",
    "Journee type d'un technicien Orange #metier",
    "Les avantages d'Orange Money que vous ignorez peut-etre #orangemoney",
    "Challenge : 24h sans reseau, on a essaye #challenge",
    "Devine ou se trouve cette antenne #quiz"
]

HASHTAGS = {
    1: ["#OrangeMadagascar", "#OrangeMoney", "#Fibre", "#4G", "#Madagascar"],
    2: ["#YasMadagascar", "#Yas", "#Telma", "#Mobile", "#Madagascar"],
    3: ["#AirtelMadagascar", "#AirtelMoney", "#4G", "#Madagascar", "#Internet"]
}


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def generer_posts(operateur_id, plateforme, compte, nb_posts):
    posts = []
    textes = TEXTES_FACEBOOK if plateforme == "facebook" else (TEXTES_INSTAGRAM if plateforme == "instagram" else TEXTES_TIKTOK)

    for i in range(nb_posts):
        texte_base = random.choice(textes)
        if plateforme == "instagram":
            texte_base += " " + " ".join(random.sample(HASHTAGS[operateur_id], k=2))

        date_post = datetime.now() - timedelta(days=random.randint(0, 60), hours=random.randint(0, 23))

        post = {
            "operateur_id": operateur_id,
            "plateforme": plateforme,
            "post_id": f"{plateforme}_{operateur_id}_{int(date_post.timestamp())}_{i}",
            "texte": texte_base,
            "likes": random.randint(50, 5000),
            "commentaires": random.randint(5, 300),
            "partages": random.randint(0, 150) if plateforme == "facebook" else 0,
            "vues": random.randint(1000, 50000) if plateforme == "tiktok" else random.randint(500, 20000),
            "date_post": date_post.strftime("%Y-%m-%d %H:%M:%S"),
            "url": f"https://www.{plateforme}.com/{compte}/posts/{int(date_post.timestamp())}"
        }
        posts.append(post)

    return posts


def inserer_post(post):
    conn = get_connection()
    try:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO social_posts
            (operateur_id, plateforme, post_id, texte, likes, commentaires, partages, vues, date_post, url)
            VALUES (%(operateur_id)s, %(plateforme)s, %(post_id)s, %(texte)s, %(likes)s, %(commentaires)s, %(partages)s, %(vues)s, %(date_post)s, %(url)s)
            ON DUPLICATE KEY UPDATE
                texte = VALUES(texte),
                likes = VALUES(likes),
                commentaires = VALUES(commentaires),
                partages = VALUES(partages),
                vues = VALUES(vues)
        """, post)
        conn.commit()
        cursor.close()
        return True
    except Exception as e:
        print(f"  Erreur insertion : {e}")
        return False
    finally:
        conn.close()


def main():
    print("=" * 50)
    print("SIMULATION SCRAPING RESEAUX SOCIAUX")
    print("=" * 50)

    total = 0
    for op_id, reseaux in COMPTES.items():
        print(f"\n>>> {reseaux['nom']} <<<")
        for plateforme in ["facebook", "instagram", "tiktok"]:
            compte = reseaux[plateforme]
            nb = random.randint(8, 15)
            posts = generer_posts(op_id, plateforme, compte, nb)
            inserres = 0
            for p in posts:
                if inserer_post(p):
                    inserres += 1
            print(f"  {plateforme.upper():10s} : {inserres} posts stockes")
            total += inserres

    print("\n" + "=" * 50)
    print(f"TERMINE - {total} posts stockes au total")
    print("=" * 50)


if __name__ == "__main__":
    main()