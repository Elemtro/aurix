<?php

declare(strict_types=1);

namespace App\Voice\Phrase;

/**
 * Curated Polish word list used to build random verification phrases.
 *
 * Selection rules: common words, 1-3 syllables, easy to pronounce,
 * phonetically varied, no homophones, no proper nouns, no offensive words.
 * Numbers are kept as words so ASR output can be compared textually.
 */
final class WordList
{
    /** @var list<string> */
    public const WORDS = [
        // food and drink
        'kawa', 'herbata', 'mleko', 'woda', 'sok', 'chleb', 'masło', 'ser', 'jajko', 'ryba',
        'mięso', 'owoc', 'jabłko', 'gruszka', 'śliwka', 'banan', 'cytryna', 'marchew', 'ziemniak', 'cebula',
        'czosnek', 'pomidor', 'ogórek', 'kapusta', 'zupa', 'kasza', 'ryż', 'makaron', 'ciasto', 'cukier',
        'sól', 'pieprz', 'miód', 'dżem', 'lody', 'tort', 'obiad', 'śniadanie', 'kolacja', 'talerz',
        'kubek', 'łyżka', 'widelec', 'nóż', 'szklanka', 'garnek', 'patelnia',

        // home and objects
        'stół', 'krzesło', 'sofa', 'łóżko', 'szafa', 'półka', 'dywan', 'okno', 'drzwi', 'ściana',
        'sufit', 'podłoga', 'lampa', 'zegar', 'lustro', 'obraz', 'firanka', 'klucz', 'zamek', 'klamka',
        'dzwonek', 'telefon', 'komputer', 'ekran', 'klawisz', 'papier', 'długopis', 'ołówek', 'notes',
        'książka', 'gazeta', 'list', 'koperta', 'znaczek', 'torba', 'plecak', 'portfel', 'parasol', 'okulary',
        'zegarek', 'pierścień', 'buty', 'kurtka', 'czapka', 'szalik', 'rękawica', 'skarpeta', 'koszula', 'spodnie',
        'sukienka', 'sweter', 'pasek', 'guzik', 'kieszeń', 'rękaw', 'kołnierz',

        // nature
        'las', 'drzewo', 'liść', 'trawa', 'kwiat', 'słońce', 'księżyc', 'gwiazda', 'niebo', 'chmura',
        'deszcz', 'śnieg', 'wiatr', 'burza', 'rzeka', 'jezioro', 'morze', 'góra', 'dolina', 'pole',
        'łąka', 'ogród', 'płot', 'brama', 'kamień', 'piasek', 'ziemia', 'ogień', 'lód', 'mgła',
        'tęcza', 'cień', 'światło', 'ciemność', 'ciepło', 'zimno', 'wiosna', 'lato', 'jesień', 'zima',
        'poranek', 'wieczór', 'noc', 'dzień', 'tydzień', 'miesiąc', 'rok', 'godzina', 'minuta', 'chwila',

        // animals
        'pies', 'kot', 'koń', 'krowa', 'owca', 'koza', 'kura', 'kaczka', 'gęś', 'królik',
        'lis', 'wilk', 'niedźwiedź', 'jeleń', 'sarna', 'dzik', 'borsuk', 'wiewiórka', 'ptak', 'wróbel',
        'sikorka', 'gołąb', 'sowa', 'orzeł', 'jaskółka', 'bocian', 'żaba', 'motyl', 'pszczoła', 'osa',
        'mrówka', 'pająk', 'mucha', 'komar', 'ślimak', 'żółw', 'wąż',

        // places
        'dom', 'szkoła', 'sklep', 'rynek', 'ulica', 'miasto', 'wieś', 'park', 'plaża', 'most',
        'dworzec', 'lotnisko', 'szpital', 'apteka', 'bank', 'kościół', 'muzeum', 'kino', 'teatr',
        'hotel', 'biuro', 'fabryka', 'warsztat', 'stacja', 'plac', 'boisko',

        // transport
        'pociąg', 'samolot', 'rower', 'samochód', 'statek', 'autobus', 'tramwaj', 'taksówka', 'motor',

        // adjectives
        'duży', 'mały', 'wysoki', 'niski', 'długi', 'krótki', 'gruby', 'cienki', 'ciężki', 'lekki',
        'szybki', 'wolny', 'nowy', 'stary', 'młody', 'ciepły', 'zimny', 'jasny', 'ciemny', 'czysty',
        'brudny', 'głośny', 'cichy', 'mocny', 'słaby', 'twardy', 'miękki', 'słodki', 'kwaśny', 'gorący',
        'mokry', 'suchy', 'pełny', 'pusty', 'drogi', 'tani', 'łatwy', 'trudny', 'ważny', 'ciekawy',
    ];

    /** @var list<string> */
    public const NUMBERS = [
        'jeden', 'dwa', 'trzy', 'cztery', 'pięć', 'sześć', 'siedem', 'osiem', 'dziewięć',
    ];
}
