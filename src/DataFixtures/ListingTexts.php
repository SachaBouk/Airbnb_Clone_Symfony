<?php

namespace App\DataFixtures;

use Faker\Generator;

final class ListingTexts
{
    private const INTRODUCTIONS = [
        'Appartement lumineux' => [
            'Bienvenue dans cet appartement baigné de lumière, avec de grandes fenêtres et un beau parquet ancien.',
            'Profitez de cet appartement clair et accueillant au deuxième étage d’un immeuble de caractère.',
        ],
        'Studio cosy' => [
            'Ce studio douillet a été pensé dans les moindres détails pour un séjour en solo ou à deux.',
            'Petit mais très bien agencé, ce studio cosy est idéal pour découvrir la ville à votre rythme.',
        ],
        'Maison de charme' => [
            'Cette maison de charme en pierre a gardé tout son cachet : poutres apparentes, tomettes et cheminée.',
            'Poussez la porte de cette maison pleine de caractère, entièrement rénovée avec goût.',
        ],
        'Loft moderne' => [
            'Ancien atelier transformé en loft, ce logement offre de grands volumes et une hauteur sous plafond impressionnante.',
            'Design épuré, verrière d’atelier et cuisine ouverte : ce loft est parfait pour les amateurs d’architecture.',
        ],
        'Villa avec piscine' => [
            'Cette villa contemporaine s’ouvre sur une grande terrasse et une piscine entourée de transats.',
            'Offrez-vous des vacances au soleil dans cette villa spacieuse, avec piscine privée et jardin arboré.',
        ],
        'Chalet chaleureux' => [
            'Tout en bois, ce chalet chaleureux est le refuge idéal après une journée au grand air.',
            'Ce chalet douillet vous accueille avec son poêle à bois et sa vue dégagée sur les montagnes.',
        ],
        'Duplex spacieux' => [
            'Réparti sur deux niveaux, ce duplex offre un vrai espace de vie et des chambres au calme à l’étage.',
            'Ce grand duplex sous les toits combine un séjour lumineux et une mezzanine très agréable.',
        ],
        'Petite maison de ville' => [
            'Nichée dans une ruelle calme, cette petite maison de ville a tout d’un cocon.',
            'Cette maison de ville typique, avec sa petite cour fleurie, vous fera vous sentir comme chez vous.',
        ],
    ];

    private const LOCATIONS = [
        'Le logement se trouve en plein cœur de %s, à quelques minutes à pied des restaurants, des boutiques et des principaux sites.',
        'Situé dans un quartier résidentiel et paisible de %s, il reste très bien desservi par les transports en commun.',
        'Vous serez idéalement placés pour explorer %s : marché, cafés et monuments sont à deux pas.',
        'Depuis ce quartier animé de %s, tout se fait à pied, et une boulangerie vous attend au coin de la rue.',
    ];

    private const INTERIORS = [
        'Le salon, avec son canapé confortable, donne sur une cuisine ouverte où il fait bon préparer le dîner.',
        'Les chambres sont équipées de lits de qualité hôtelière, avec linge de maison et serviettes fournis.',
        'La salle de bains moderne dispose d’une grande douche à l’italienne.',
        'Une table pour six personnes permet de partager de bons repas en famille ou entre amis.',
    ];

    private const PRACTICAL = [
        'L’arrivée est autonome grâce à une boîte à clés, et je reste joignable pendant tout votre séjour.',
        'Ce sera un plaisir de vous accueillir en personne et de vous partager mes meilleures adresses.',
        'Le logement est non-fumeur ; les fêtes et les événements ne sont pas autorisés.',
        'Un guide avec mes recommandations de restaurants et de balades vous attend à votre arrivée.',
    ];

    private const REVIEWS = [
        5 => [
            'Séjour parfait ! Le logement est encore plus beau qu’en photo et %1$s a été aux petits soins. Je recommande les yeux fermés.',
            'Tout était impeccable : propreté, literie, emplacement. On a adoré se balader dans %2$s depuis l’appartement.',
            'Un vrai coup de cœur. %1$s nous a donné plein de bonnes adresses et le logement est très bien équipé.',
            'Calme, lumineux et idéalement situé. Nous reviendrons sans hésiter lors de notre prochain passage à %2$s.',
            'Accueil chaleureux, logement superbe et décoré avec goût. Merci %1$s pour ce séjour mémorable !',
        ],
        4 => [
            'Très bon séjour dans un logement agréable et bien placé. Seul petit bémol : un peu de bruit de la rue le soir.',
            'Logement conforme à l’annonce et hôte très réactif. La cuisine mériterait juste quelques ustensiles de plus.',
            'Bel endroit pour découvrir %2$s. La literie est confortable, nous avons très bien dormi.',
            'Bonne expérience dans l’ensemble, %1$s est très sympathique. Le wifi était un peu lent mais rien de grave.',
        ],
        3 => [
            'Séjour correct. Le logement est bien situé mais aurait besoin d’un petit rafraîchissement.',
            'L’emplacement est top, en revanche le ménage n’était pas tout à fait fait à notre arrivée.',
            'Rapport qualité-prix moyen. Le logement est fonctionnel mais moins chaleureux qu’en photo.',
        ],
        2 => [
            'Déçus par la propreté à notre arrivée et par le bruit la nuit. %1$s a tout de même essayé de trouver une solution.',
            'Le logement ne correspond pas vraiment aux photos et le chauffage fonctionnait mal.',
        ],
        1 => [
            'Très mauvaise expérience : logement sale et hôte injoignable pendant notre séjour. À éviter.',
            'Rien ne correspondait à l’annonce et nous avons dû écourter notre séjour. Vraiment dommage.',
        ],
    ];

    public const TYPE_WITH_POOL = 'Villa avec piscine';

    public static function listingTypes(): array
    {
        return array_keys(self::INTRODUCTIONS);
    }

    public static function description(Generator $faker, string $listingType, string $city, array $amenityNames): string
    {
        $paragraphs = [
            $faker->randomElement(self::INTRODUCTIONS[$listingType]).' '.\sprintf($faker->randomElement(self::LOCATIONS), $city),
            implode(' ', $faker->randomElements(self::INTERIORS, 2)),
        ];

        $practical = $faker->randomElement(self::PRACTICAL);
        if ([] !== $amenityNames) {
            $practical = 'Côté équipements, vous profiterez de : '.mb_strtolower(implode(', ', $amenityNames)).'. '.$practical;
        }
        $paragraphs[] = $practical;

        return implode("\n\n", $paragraphs);
    }

    public static function review(Generator $faker, int $rating, string $hostFirstName, string $city): string
    {
        return \sprintf($faker->randomElement(self::REVIEWS[$rating]), $hostFirstName, $city);
    }
}
