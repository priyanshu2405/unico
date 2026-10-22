<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

use App\Services\CurrencyService;

class CurrencyHelper
{
    public static function convert($amount, $from, $to)
    {
        return CurrencyService::convert((float)$amount, (string)$from, (string)$to);
    }

    public static function getSymbol(?string $currency): string
    {
        $clean = strtoupper(trim($currency ?? 'USD'));
        $currencies = self::getAllCurrencies();
        return $currencies[$clean]['symbol'] ?? ($currency ?: '$');
    }

    /**
     * Get a comprehensive list of all world country currencies.
     * Popular and regional currencies are placed at the top for convenience.
     *
     * @return array<string, array{code: string, name: string, symbol: string}>
     */
    public static function getAllCurrencies(): array
    {
        return [
            // Priority & Most Common
            'USD' => ['code' => 'USD', 'name' => 'United States Dollar', 'symbol' => '$'],
            'INR' => ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹'],
            'PKR' => ['code' => 'PKR', 'name' => 'Pakistani Rupee', 'symbol' => 'Rs'],
            'GBP' => ['code' => 'GBP', 'name' => 'British Pound Sterling', 'symbol' => '£'],
            'EUR' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            'AED' => ['code' => 'AED', 'name' => 'United Arab Emirates Dirham', 'symbol' => 'د.إ'],
            'CAD' => ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CA$'],
            'AUD' => ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'AU$'],
            'SAR' => ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => '﷼'],
            'QAR' => ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => '﷼'],
            'KWD' => ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KD'],
            'BDT' => ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳'],
            'NPR' => ['code' => 'NPR', 'name' => 'Nepalese Rupee', 'symbol' => 'Rs'],
            'TRY' => ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => '₺'],
            'SGD' => ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$'],
            'MYR' => ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
            'JPY' => ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
            'CNY' => ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥'],
            'CHF' => ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF'],
            'NZD' => ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$'],
            'ZAR' => ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R'],

            // Alphabetical Global Currencies
            'AFN' => ['code' => 'AFN', 'name' => 'Afghan Afghani', 'symbol' => '؋'],
            'ALL' => ['code' => 'ALL', 'name' => 'Albanian Lek', 'symbol' => 'L'],
            'AMD' => ['code' => 'AMD', 'name' => 'Armenian Dram', 'symbol' => '֏'],
            'ANG' => ['code' => 'ANG', 'name' => 'Netherlands Antillean Guilder', 'symbol' => 'ƒ'],
            'AOA' => ['code' => 'AOA', 'name' => 'Angolan Kwanza', 'symbol' => 'Kz'],
            'ARS' => ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => '$'],
            'AWG' => ['code' => 'AWG', 'name' => 'Aruban Florin', 'symbol' => 'ƒ'],
            'AZN' => ['code' => 'AZN', 'name' => 'Azerbaijani Manat', 'symbol' => '₼'],
            'BAM' => ['code' => 'BAM', 'name' => 'Bosnia-Herzegovina Convertible Mark', 'symbol' => 'KM'],
            'BBD' => ['code' => 'BBD', 'name' => 'Barbadian Dollar', 'symbol' => '$'],
            'BGN' => ['code' => 'BGN', 'name' => 'Bulgarian Lev', 'symbol' => 'лв'],
            'BHD' => ['code' => 'BHD', 'name' => 'Bahraini Dinar', 'symbol' => '.د.ب'],
            'BIF' => ['code' => 'BIF', 'name' => 'Burundian Franc', 'symbol' => 'FBu'],
            'BMD' => ['code' => 'BMD', 'name' => 'Bermudan Dollar', 'symbol' => '$'],
            'BND' => ['code' => 'BND', 'name' => 'Brunei Dollar', 'symbol' => '$'],
            'BOB' => ['code' => 'BOB', 'name' => 'Bolivian Boliviano', 'symbol' => 'Bs.'],
            'BRL' => ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
            'BSD' => ['code' => 'BSD', 'name' => 'Bahamian Dollar', 'symbol' => '$'],
            'BTN' => ['code' => 'BTN', 'name' => 'Bhutanese Ngultrum', 'symbol' => 'Nu.'],
            'BWP' => ['code' => 'BWP', 'name' => 'Botswanan Pula', 'symbol' => 'P'],
            'BYN' => ['code' => 'BYN', 'name' => 'Belarusian Ruble', 'symbol' => 'Br'],
            'BZD' => ['code' => 'BZD', 'name' => 'Belize Dollar', 'symbol' => 'BZ$'],
            'CDF' => ['code' => 'CDF', 'name' => 'Congolese Franc', 'symbol' => 'FC'],
            'CLP' => ['code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => '$'],
            'COP' => ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => '$'],
            'CRC' => ['code' => 'CRC', 'name' => 'Costa Rican Colón', 'symbol' => '₡'],
            'CUP' => ['code' => 'CUP', 'name' => 'Cuban Peso', 'symbol' => '₱'],
            'CVE' => ['code' => 'CVE', 'name' => 'Cape Verdean Escudo', 'symbol' => '$'],
            'CZK' => ['code' => 'CZK', 'name' => 'Czech Koruna', 'symbol' => 'Kč'],
            'DJF' => ['code' => 'DJF', 'name' => 'Djiboutian Franc', 'symbol' => 'Fdj'],
            'DKK' => ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr'],
            'DOP' => ['code' => 'DOP', 'name' => 'Dominican Peso', 'symbol' => 'RD$'],
            'DZD' => ['code' => 'DZD', 'name' => 'Algerian Dinar', 'symbol' => 'دج'],
            'EGP' => ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => '£'],
            'ERN' => ['code' => 'ERN', 'name' => 'Eritrean Nakfa', 'symbol' => 'Nfk'],
            'ETB' => ['code' => 'ETB', 'name' => 'Ethiopian Birr', 'symbol' => 'Br'],
            'FJD' => ['code' => 'FJD', 'name' => 'Fijian Dollar', 'symbol' => '$'],
            'FKP' => ['code' => 'FKP', 'name' => 'Falkland Islands Pound', 'symbol' => '£'],
            'GEL' => ['code' => 'GEL', 'name' => 'Georgian Lari', 'symbol' => '₾'],
            'GHS' => ['code' => 'GHS', 'name' => 'Ghanaian Cedi', 'symbol' => 'GH₵'],
            'GIP' => ['code' => 'GIP', 'name' => 'Gibraltar Pound', 'symbol' => '£'],
            'GMD' => ['code' => 'GMD', 'name' => 'Gambian Dalasi', 'symbol' => 'D'],
            'GNF' => ['code' => 'GNF', 'name' => 'Guinean Franc', 'symbol' => 'FG'],
            'GTQ' => ['code' => 'GTQ', 'name' => 'Guatemalan Quetzal', 'symbol' => 'Q'],
            'GYD' => ['code' => 'GYD', 'name' => 'Guyanaese Dollar', 'symbol' => '$'],
            'HKD' => ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => 'HK$'],
            'HNL' => ['code' => 'HNL', 'name' => 'Honduran Lempira', 'symbol' => 'L'],
            'HRK' => ['code' => 'HRK', 'name' => 'Croatian Kuna', 'symbol' => 'kn'],
            'HTG' => ['code' => 'HTG', 'name' => 'Haitian Gourde', 'symbol' => 'G'],
            'HUF' => ['code' => 'HUF', 'name' => 'Hungarian Forint', 'symbol' => 'Ft'],
            'IDR' => ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp'],
            'ILS' => ['code' => 'ILS', 'name' => 'Israeli New Shekel', 'symbol' => '₪'],
            'IQD' => ['code' => 'IQD', 'name' => 'Iraqi Dinar', 'symbol' => 'ع.د'],
            'IRR' => ['code' => 'IRR', 'name' => 'Iranian Rial', 'symbol' => '﷼'],
            'ISK' => ['code' => 'ISK', 'name' => 'Icelandic Króna', 'symbol' => 'kr'],
            'JMD' => ['code' => 'JMD', 'name' => 'Jamaican Dollar', 'symbol' => 'J$'],
            'JOD' => ['code' => 'JOD', 'name' => 'Jordanian Dinar', 'symbol' => 'JD'],
            'KES' => ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh'],
            'KGS' => ['code' => 'KGS', 'name' => 'Kyrgystani Som', 'symbol' => 'с'],
            'KHR' => ['code' => 'KHR', 'name' => 'Cambodian Riel', 'symbol' => '៛'],
            'KMF' => ['code' => 'KMF', 'name' => 'Comorian Franc', 'symbol' => 'CF'],
            'KRW' => ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩'],
            'KYD' => ['code' => 'KYD', 'name' => 'Cayman Islands Dollar', 'symbol' => '$'],
            'KZT' => ['code' => 'KZT', 'name' => 'Kazakhstani Tenge', 'symbol' => '₸'],
            'LAK' => ['code' => 'LAK', 'name' => 'Laotian Kip', 'symbol' => '₭'],
            'LBP' => ['code' => 'LBP', 'name' => 'Lebanese Pound', 'symbol' => 'ل.ل'],
            'LKR' => ['code' => 'LKR', 'name' => 'Sri Lankan Rupee', 'symbol' => 'Rs'],
            'LRD' => ['code' => 'LRD', 'name' => 'Liberian Dollar', 'symbol' => '$'],
            'LSL' => ['code' => 'LSL', 'name' => 'Lesotho Loti', 'symbol' => 'L'],
            'LYD' => ['code' => 'LYD', 'name' => 'Libyan Dinar', 'symbol' => 'LD'],
            'MAD' => ['code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'DH'],
            'MDL' => ['code' => 'MDL', 'name' => 'Moldovan Leu', 'symbol' => 'L'],
            'MGA' => ['code' => 'MGA', 'name' => 'Malagasy Ariary', 'symbol' => 'Ar'],
            'MKD' => ['code' => 'MKD', 'name' => 'Macedonian Denar', 'symbol' => 'ден'],
            'MMK' => ['code' => 'MMK', 'name' => 'Myanmar Kyat', 'symbol' => 'K'],
            'MNT' => ['code' => 'MNT', 'name' => 'Mongolian Tugrik', 'symbol' => '₮'],
            'MOP' => ['code' => 'MOP', 'name' => 'Macanese Pataca', 'symbol' => 'MOP$'],
            'MRU' => ['code' => 'MRU', 'name' => 'Mauritanian Ouguiya', 'symbol' => 'UM'],
            'MUR' => ['code' => 'MUR', 'name' => 'Mauritian Rupee', 'symbol' => '₨'],
            'MVR' => ['code' => 'MVR', 'name' => 'Maldivian Rufiyaa', 'symbol' => 'Rf'],
            'MWK' => ['code' => 'MWK', 'name' => 'Malawian Kwacha', 'symbol' => 'MK'],
            'MXN' => ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$'],
            'MZN' => ['code' => 'MZN', 'name' => 'Mozambican Metical', 'symbol' => 'MT'],
            'NAD' => ['code' => 'NAD', 'name' => 'Namibian Dollar', 'symbol' => '$'],
            'NGN' => ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '₦'],
            'NIO' => ['code' => 'NIO', 'name' => 'Nicaraguan Córdoba', 'symbol' => 'C$'],
            'NOK' => ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr'],
            'OMR' => ['code' => 'OMR', 'name' => 'Omani Rial', 'symbol' => 'ر.ع.'],
            'PAB' => ['code' => 'PAB', 'name' => 'Panamanian Balboa', 'symbol' => 'B/.'],
            'PEN' => ['code' => 'PEN', 'name' => 'Peruvian Sol', 'symbol' => 'S/.'],
            'PGK' => ['code' => 'PGK', 'name' => 'Papua New Guinean Kina', 'symbol' => 'K'],
            'PHP' => ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱'],
            'PLN' => ['code' => 'PLN', 'name' => 'Polish Zloty', 'symbol' => 'zł'],
            'PYG' => ['code' => 'PYG', 'name' => 'Paraguayan Guarani', 'symbol' => '₲'],
            'RON' => ['code' => 'RON', 'name' => 'Romanian Leu', 'symbol' => 'lei'],
            'RSD' => ['code' => 'RSD', 'name' => 'Serbian Dinar', 'symbol' => 'дин.'],
            'RUB' => ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽'],
            'RWF' => ['code' => 'RWF', 'name' => 'Rwandan Franc', 'symbol' => 'FRw'],
            'SBD' => ['code' => 'SBD', 'name' => 'Solomon Islands Dollar', 'symbol' => '$'],
            'SCR' => ['code' => 'SCR', 'name' => 'Seychellois Rupee', 'symbol' => '₨'],
            'SDG' => ['code' => 'SDG', 'name' => 'Sudanese Pound', 'symbol' => '£'],
            'SEK' => ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr'],
            'SOS' => ['code' => 'SOS', 'name' => 'Somali Shilling', 'symbol' => 'Sh.So.'],
            'SRD' => ['code' => 'SRD', 'name' => 'Surinamese Dollar', 'symbol' => '$'],
            'SSP' => ['code' => 'SSP', 'name' => 'South Sudanese Pound', 'symbol' => '£'],
            'STN' => ['code' => 'STN', 'name' => 'São Tomé & Príncipe Dobra', 'symbol' => 'Db'],
            'THB' => ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿'],
            'TJS' => ['code' => 'TJS', 'name' => 'Tajikistani Somoni', 'symbol' => 'ЅМ'],
            'TMT' => ['code' => 'TMT', 'name' => 'Turkmenistani Manat', 'symbol' => 'T'],
            'TND' => ['code' => 'TND', 'name' => 'Tunisian Dinar', 'symbol' => 'د.ت'],
            'TOP' => ['code' => 'TOP', 'name' => 'Tongan Paʻanga', 'symbol' => 'T$'],
            'TTD' => ['code' => 'TTD', 'name' => 'Trinidad & Tobago Dollar', 'symbol' => 'TT$'],
            'TWD' => ['code' => 'TWD', 'name' => 'New Taiwan Dollar', 'symbol' => 'NT$'],
            'TZS' => ['code' => 'TZS', 'name' => 'Tanzanian Shilling', 'symbol' => 'TSh'],
            'UAH' => ['code' => 'UAH', 'name' => 'Ukrainian Hryvnia', 'symbol' => '₴'],
            'UGX' => ['code' => 'UGX', 'name' => 'Ugandan Shilling', 'symbol' => 'USh'],
            'UYU' => ['code' => 'UYU', 'name' => 'Uruguayan Peso', 'symbol' => '$U'],
            'UZS' => ['code' => 'UZS', 'name' => 'Uzbekistani Som', 'symbol' => 'so\'m'],
            'VES' => ['code' => 'VES', 'name' => 'Venezuelan Bolívar', 'symbol' => 'Bs.'],
            'VND' => ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => '₫'],
            'VUV' => ['code' => 'VUV', 'name' => 'Vanuatu Vatu', 'symbol' => 'VT'],
            'WST' => ['code' => 'WST', 'name' => 'Samoan Tala', 'symbol' => 'WS$'],
            'XAF' => ['code' => 'XAF', 'name' => 'Central African CFA Franc', 'symbol' => 'FCFA'],
            'XCD' => ['code' => 'XCD', 'name' => 'East Caribbean Dollar', 'symbol' => 'EC$'],
            'XOF' => ['code' => 'XOF', 'name' => 'West African CFA Franc', 'symbol' => 'CFA'],
            'XPF' => ['code' => 'XPF', 'name' => 'CFP Franc', 'symbol' => '₣'],
            'YER' => ['code' => 'YER', 'name' => 'Yemeni Rial', 'symbol' => '﷼'],
            'ZMW' => ['code' => 'ZMW', 'name' => 'Zambian Kwacha', 'symbol' => 'ZK'],
            'ZWL' => ['code' => 'ZWL', 'name' => 'Zimbabwean Dollar', 'symbol' => 'Z$'],
        ];
    }
}