type Category = 'villa' | 'chalet' | 'house' | 'apartment' | 'loft';

const COVERS: Record<Category, string[]> = {
  villa: [
    '1512917774080-9991f1c4c750', '1564013799919-ab600027ffc6', '1580587771525-78b9dba3b914',
    '1600596542815-ffad4c1539a9', '1613490493576-7fde63acd811', '1582268611958-ebfd161ef9cf',
    '1600585154340-be6161a56a0c', '1416331108676-a22ccb276e35', '1513584684374-8bab748fbf90',
    '1566073771259-6a8506099945', '1523217582562-09d0def993a6',
  ],
  chalet: [
    '1510798831971-661eb04b3739', '1449158743715-0a90ebb6d2d8', '1542718610-a1d656d1884c',
    '1587061949409-02df41d5e562', '1595521624992-48a59aef95e3', '1470770841072-f978cf4d019e',
    '1568605114967-8130f3a36994', '1518780664697-55e3ad937233',
  ],
  house: [
    '1475855581690-80accde3ae2b', '1464146072230-91cabc968266', '1588880331179-bc9b93a8cb5e',
    '1572120360610-d971b9d7767c', '1570129477492-45c003edd2be', '1583608205776-bfd35f0d9f83',
    '1449844908441-8829872d2607', '1494526585095-c41746248156',
  ],

  apartment: [
    '1545324418-cc1a3fa10c00', '1460317442991-0ec209397118', '1551361415-69c87624334f',
    '1574362848149-11496d93a7c7', '1522050212171-61b01dd24579', '1554995207-c18c203602cb', '1600607687939-ce8a6c25118c',
    '1600210492486-724fe5c67fb0', '1618221195710-dd6b41faaea6', '1513694203232-719a280e022f',
    '1484101403633-562f891dc89a', '1522708323590-d24dbb6b0267', '1502672260266-1c1ef2d93688',
  ],
  loft: [
    '1505873242700-f289a29e1e0f', '1501183638710-841dd1904471', '1499955085172-a104c9463ece',
    '1536376072261-38c75010e6c9', '1598928506311-c55ded91a20c',
  ],
};

const INTERIORS = [
  '1505691938895-1758d7feb511', '1522708323590-d24dbb6b0267', '1502672260266-1c1ef2d93688',
  '1560448204-e02f11c3d0e2', '1493809842364-78817add7ffb', '1484154218962-a197022b5858',
  '1615874959474-d609969a20ed', '1586023492125-27b2c045efd7', '1505693416388-ac5ce068fe85',
  '1540518614846-7eded433c457', '1600607687939-ce8a6c25118c', '1600566753190-17f0baa2a6c3',
  '1600210492486-724fe5c67fb0', '1616594039964-ae9021a400a0', '1631049307264-da0ec9d70304',
  '1617806118233-18e1de247200', '1618221195710-dd6b41faaea6', '1600494603989-9650cf6ddd3d',
  '1513694203232-719a280e022f', '1560185007-cde436f6a4d0', '1560185893-a55cbc8c57e8',
  '1598928506311-c55ded91a20c', '1522771739844-6a9f6d5f14af', '1484101403633-562f891dc89a',
];

const KEYWORDS: [Category, string[]][] = [
  ['loft', ['loft', 'atelier']],
  ['chalet', ['chalet', 'cabane', 'refuge']],
  ['villa', ['villa', 'piscine', 'riad']],
  ['apartment', ['appartement', 'studio', 'duplex', 'altbau', 'peniche']],
  ['house', ['maison', 'ferme', 'mas ', 'cottage']],
];

function categoryOf(title: string): Category {
  const normalized = title.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  const match = KEYWORDS.find(([, words]) => words.some((word) => normalized.includes(word)));

  return match?.[0] ?? 'house';
}

interface PhotoSubject {
  id: number;
  title: string;

  coverUrl?: string | null;
}

export function listingCover(listing: PhotoSubject, width = 720, height = 680): string {
  return listing.coverUrl ?? placeholderPhoto(listing, 0, width, height);
}

export function galleryPhotos(listing: PhotoSubject & { photos: { url: string }[] }): string[] {
  if (listing.photos.length > 0) {
    return listing.photos.slice(0, 5).map((photo) => photo.url);
  }

  return [0, 1, 2, 3, 4].map((index) => placeholderPhoto(listing, index, index === 0 ? 1200 : 600, index === 0 ? 900 : 450));
}

function placeholderPhoto(listing: PhotoSubject, index: number, width: number, height: number): string {
  const cover = pick(COVERS[categoryOf(listing.title)], listing.id);
  let photoId = cover;

  if (index > 0) {

    const interiors = INTERIORS.filter((interior) => interior !== cover);
    photoId = pick(interiors, listing.id * 3 + index - 1);
  }

  return `https://images.unsplash.com/photo-${photoId}?w=${width}&h=${height}&fit=crop&auto=format&q=75`;
}

function pick(photos: string[], seed: number): string {
  return photos[seed % photos.length];
}

const AVATAR_COLORS = ['#222222', '#e0565b', '#008489', '#914669', '#c13515', '#428bff', '#3f8a3a'];

export function avatarColor(userId: number): string {
  return AVATAR_COLORS[userId % AVATAR_COLORS.length];
}
