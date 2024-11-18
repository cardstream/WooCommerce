<?php

namespace P3\SDK;


class AmountHelper
{
    private const AVAILABLE_CURRENCIES = array (
        'GBP' =>
            array (
                'active' => 'Y',
                'iso_num' => '826',
                'iso_code' => 'GBP',
                'name' => 'United Kingdom Pound',
                'symbol' => '£',
                'exponent' => '2',
            ),
        'USD' =>
            array (
                'active' => 'Y',
                'iso_num' => '840',
                'iso_code' => 'USD',
                'name' => 'United States Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'EUR' =>
            array (
                'active' => 'Y',
                'iso_num' => '978',
                'iso_code' => 'EUR',
                'name' => 'Euro Member Countries',
                'symbol' => '€',
                'exponent' => '2',
            ),
        'AUD' =>
            array (
                'active' => 'Y',
                'iso_num' => '036',
                'iso_code' => 'AUD',
                'name' => 'Australia Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'CAD' =>
            array (
                'active' => 'Y',
                'iso_num' => '124',
                'iso_code' => 'CAD',
                'name' => 'Canada Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'CZK' =>
            array (
                'active' => 'Y',
                'iso_num' => '203',
                'iso_code' => 'CZK',
                'name' => 'Czech Republic Koruna',
                'symbol' => 'Kč',
                'exponent' => '2',
            ),
        'DKK' =>
            array (
                'active' => 'Y',
                'iso_num' => '208',
                'iso_code' => 'DKK',
                'name' => 'Denmark Krone',
                'symbol' => 'kr',
                'exponent' => '2',
            ),
        'HKD' =>
            array (
                'active' => 'Y',
                'iso_num' => '344',
                'iso_code' => 'HKD',
                'name' => 'Hong Kong Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'ISK' =>
            array (
                'active' => 'Y',
                'iso_num' => '352',
                'iso_code' => 'ISK',
                'name' => 'Iceland Krona',
                'symbol' => 'kr',
                'exponent' => '0',
            ),
        'JPY' =>
            array (
                'active' => 'Y',
                'iso_num' => '392',
                'iso_code' => 'JPY',
                'name' => 'Japan Yen',
                'symbol' => '¥',
                'exponent' => '0',
            ),
        'NOK' =>
            array (
                'active' => 'Y',
                'iso_num' => '578',
                'iso_code' => 'NOK',
                'name' => 'Norway Krone',
                'symbol' => 'kr',
                'exponent' => '2',
            ),
        'SGD' =>
            array (
                'active' => 'Y',
                'iso_num' => '702',
                'iso_code' => 'SGD',
                'name' => 'Singapore Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'SEK' =>
            array (
                'active' => 'Y',
                'iso_num' => '752',
                'iso_code' => 'SEK',
                'name' => 'Sweden Krona',
                'symbol' => 'kr',
                'exponent' => '2',
            ),
        'CHF' =>
            array (
                'active' => 'Y',
                'iso_num' => '756',
                'iso_code' => 'CHF',
                'name' => 'Switzerland Franc',
                'symbol' => 'CHF',
                'exponent' => '2',
            ),
        'ALL' =>
            array (
                'active' => 'Y',
                'iso_num' => '008',
                'iso_code' => 'ALL',
                'name' => 'Albania Lek',
                'symbol' => 'Lek',
                'exponent' => '2',
            ),
        'ARS' =>
            array (
                'active' => 'Y',
                'iso_num' => '032',
                'iso_code' => 'ARS',
                'name' => 'Argentina Peso',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'BSD' =>
            array (
                'active' => 'Y',
                'iso_num' => '044',
                'iso_code' => 'BSD',
                'name' => 'Bahamas Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'BBD' =>
            array (
                'active' => 'Y',
                'iso_num' => '052',
                'iso_code' => 'BBD',
                'name' => 'Barbados Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'BYR' =>
            array (
                'active' => 'Y',
                'iso_num' => '974',
                'iso_code' => 'BYR',
                'name' => 'Belarus Ruble',
                'symbol' => 'p.',
                'exponent' => '0',
            ),
        'BZD' =>
            array (
                'active' => 'Y',
                'iso_num' => '084',
                'iso_code' => 'BZD',
                'name' => 'Belize Dollar',
                'symbol' => 'BZ$',
                'exponent' => '2',
            ),
        'BMD' =>
            array (
                'active' => 'Y',
                'iso_num' => '060',
                'iso_code' => 'BMD',
                'name' => 'Bermuda Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'BOB' =>
            array (
                'active' => 'Y',
                'iso_num' => '068',
                'iso_code' => 'BOB',
                'name' => 'Bolivia Boliviano',
                'symbol' => '$b',
                'exponent' => '2',
            ),
        'BAM' =>
            array (
                'active' => 'Y',
                'iso_num' => '977',
                'iso_code' => 'BAM',
                'name' => 'Bosnia and Herzegovina Convertible Marka',
                'symbol' => 'KM',
                'exponent' => '2',
            ),
        'BWP' =>
            array (
                'active' => 'Y',
                'iso_num' => '072',
                'iso_code' => 'BWP',
                'name' => 'Botswana Pula',
                'symbol' => 'P',
                'exponent' => '2',
            ),
        'BGN' =>
            array (
                'active' => 'Y',
                'iso_num' => '975',
                'iso_code' => 'BGN',
                'name' => 'Bulgaria Lev',
                'symbol' => 'лв',
                'exponent' => '2',
            ),
        'BRL' =>
            array (
                'active' => 'Y',
                'iso_num' => '986',
                'iso_code' => 'BRL',
                'name' => 'Brazil Real',
                'symbol' => 'R$',
                'exponent' => '2',
            ),
        'BND' =>
            array (
                'active' => 'Y',
                'iso_num' => '096',
                'iso_code' => 'BND',
                'name' => 'Brunei Darussalam Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'KHR' =>
            array (
                'active' => 'Y',
                'iso_num' => '116',
                'iso_code' => 'KHR',
                'name' => 'Cambodia Riel',
                'symbol' => '៛',
                'exponent' => '2',
            ),
        'KYD' =>
            array (
                'active' => 'Y',
                'iso_num' => '136',
                'iso_code' => 'KYD',
                'name' => 'Cayman Islands Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'CLP' =>
            array (
                'active' => 'Y',
                'iso_num' => '152',
                'iso_code' => 'CLP',
                'name' => 'Chile Peso',
                'symbol' => '$',
                'exponent' => '0',
            ),
        'CNY' =>
            array (
                'active' => 'Y',
                'iso_num' => '156',
                'iso_code' => 'CNY',
                'name' => 'China Yuan Renminbi',
                'symbol' => '¥',
                'exponent' => '2',
            ),
        'COP' =>
            array (
                'active' => 'Y',
                'iso_num' => '170',
                'iso_code' => 'COP',
                'name' => 'Colombia Peso',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'CRC' =>
            array (
                'active' => 'Y',
                'iso_num' => '188',
                'iso_code' => 'CRC',
                'name' => 'Costa Rica Colon',
                'symbol' => '₡',
                'exponent' => '2',
            ),
        'HRK' =>
            array (
                'active' => 'Y',
                'iso_num' => '191',
                'iso_code' => 'HRK',
                'name' => 'Croatia Kuna',
                'symbol' => 'kn',
                'exponent' => '2',
            ),
        'CUP' =>
            array (
                'active' => 'Y',
                'iso_num' => '192',
                'iso_code' => 'CUP',
                'name' => 'Cuba Peso',
                'symbol' => '₱',
                'exponent' => '2',
            ),
        'DOP' =>
            array (
                'active' => 'Y',
                'iso_num' => '214',
                'iso_code' => 'DOP',
                'name' => 'Dominican Republic Peso',
                'symbol' => 'RD$',
                'exponent' => '2',
            ),
        'XCD' =>
            array (
                'active' => 'Y',
                'iso_num' => '951',
                'iso_code' => 'XCD',
                'name' => 'East Caribbean Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'EGP' =>
            array (
                'active' => 'Y',
                'iso_num' => '818',
                'iso_code' => 'EGP',
                'name' => 'Egypt Pound',
                'symbol' => '£',
                'exponent' => '2',
            ),
        'SVC' =>
            array (
                'active' => 'Y',
                'iso_num' => '222',
                'iso_code' => 'SVC',
                'name' => 'El Salvador Colon',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'FKP' =>
            array (
                'active' => 'Y',
                'iso_num' => '238',
                'iso_code' => 'FKP',
                'name' => 'Falkland Islands (Malvinas) Pound',
                'symbol' => '£',
                'exponent' => '2',
            ),
        'FJD' =>
            array (
                'active' => 'Y',
                'iso_num' => '242',
                'iso_code' => 'FJD',
                'name' => 'Fiji Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'GIP' =>
            array (
                'active' => 'Y',
                'iso_num' => '292',
                'iso_code' => 'GIP',
                'name' => 'Gibraltar Pound',
                'symbol' => '£',
                'exponent' => '2',
            ),
        'GTQ' =>
            array (
                'active' => 'Y',
                'iso_num' => '320',
                'iso_code' => 'GTQ',
                'name' => 'Guatemala Quetzal',
                'symbol' => 'Q',
                'exponent' => '2',
            ),
        'GYD' =>
            array (
                'active' => 'Y',
                'iso_num' => '328',
                'iso_code' => 'GYD',
                'name' => 'Guyana Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'HNL' =>
            array (
                'active' => 'Y',
                'iso_num' => '340',
                'iso_code' => 'HNL',
                'name' => 'Honduras Lempira',
                'symbol' => 'L',
                'exponent' => '2',
            ),
        'HUF' =>
            array (
                'active' => 'Y',
                'iso_num' => '348',
                'iso_code' => 'HUF',
                'name' => 'Hungary Forint',
                'symbol' => 'Ft',
                'exponent' => '2',
            ),
        'INR' =>
            array (
                'active' => 'Y',
                'iso_num' => '356',
                'iso_code' => 'INR',
                'name' => 'India Rupee',
                'symbol' => '₨',
                'exponent' => '2',
            ),
        'IDR' =>
            array (
                'active' => 'Y',
                'iso_num' => '360',
                'iso_code' => 'IDR',
                'name' => 'Indonesia Rupiah',
                'symbol' => 'Rp',
                'exponent' => '2',
            ),
        'IRR' =>
            array (
                'active' => 'Y',
                'iso_num' => '364',
                'iso_code' => 'IRR',
                'name' => 'Iran Rial',
                'symbol' => '﷼',
                'exponent' => '2',
            ),
        'ILS' =>
            array (
                'active' => 'Y',
                'iso_num' => '376',
                'iso_code' => 'ILS',
                'name' => 'Israel Shekel',
                'symbol' => '₪',
                'exponent' => '2',
            ),
        'JMD' =>
            array (
                'active' => 'Y',
                'iso_num' => '388',
                'iso_code' => 'JMD',
                'name' => 'Jamaica Dollar',
                'symbol' => 'J$',
                'exponent' => '2',
            ),
        'KZT' =>
            array (
                'active' => 'Y',
                'iso_num' => '398',
                'iso_code' => 'KZT',
                'name' => 'Kazakhstan Tenge',
                'symbol' => 'лв',
                'exponent' => '2',
            ),
        'KPW' =>
            array (
                'active' => 'Y',
                'iso_num' => '408',
                'iso_code' => 'KPW',
                'name' => 'Korea (North) Won',
                'symbol' => '₩',
                'exponent' => '2',
            ),
        'KRW' =>
            array (
                'active' => 'Y',
                'iso_num' => '410',
                'iso_code' => 'KRW',
                'name' => 'Korea (South) Won',
                'symbol' => '₩',
                'exponent' => '0',
            ),
        'KGS' =>
            array (
                'active' => 'Y',
                'iso_num' => '417',
                'iso_code' => 'KGS',
                'name' => 'Kyrgyzstan Som',
                'symbol' => 'лв',
                'exponent' => '2',
            ),
        'LAK' =>
            array (
                'active' => 'Y',
                'iso_num' => '418',
                'iso_code' => 'LAK',
                'name' => 'Laos Kip',
                'symbol' => '₭',
                'exponent' => '2',
            ),
        'LVL' =>
            array (
                'active' => 'Y',
                'iso_num' => '428',
                'iso_code' => 'LVL',
                'name' => 'Latvia Lat',
                'symbol' => 'Ls',
                'exponent' => '2',
            ),
        'LBP' =>
            array (
                'active' => 'Y',
                'iso_num' => '422',
                'iso_code' => 'LBP',
                'name' => 'Lebanon Pound',
                'symbol' => '£',
                'exponent' => '2',
            ),
        'LRD' =>
            array (
                'active' => 'Y',
                'iso_num' => '430',
                'iso_code' => 'LRD',
                'name' => 'Liberia Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'LTL' =>
            array (
                'active' => 'Y',
                'iso_num' => '440',
                'iso_code' => 'LTL',
                'name' => 'Lithuania Litas',
                'symbol' => 'Lt',
                'exponent' => '2',
            ),
        'MKD' =>
            array (
                'active' => 'Y',
                'iso_num' => '807',
                'iso_code' => 'MKD',
                'name' => 'Macedonia Denar',
                'symbol' => 'ден',
                'exponent' => '2',
            ),
        'MYR' =>
            array (
                'active' => 'Y',
                'iso_num' => '458',
                'iso_code' => 'MYR',
                'name' => 'Malaysia Ringgit',
                'symbol' => 'RM',
                'exponent' => '2',
            ),
        'MUR' =>
            array (
                'active' => 'Y',
                'iso_num' => '480',
                'iso_code' => 'MUR',
                'name' => 'Mauritius Rupee',
                'symbol' => '₨',
                'exponent' => '2',
            ),
        'MXN' =>
            array (
                'active' => 'Y',
                'iso_num' => '484',
                'iso_code' => 'MXN',
                'name' => 'Mexico Peso',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'MNT' =>
            array (
                'active' => 'Y',
                'iso_num' => '496',
                'iso_code' => 'MNT',
                'name' => 'Mongolia Tughrik',
                'symbol' => '₮',
                'exponent' => '2',
            ),
        'MZN' =>
            array (
                'active' => 'Y',
                'iso_num' => '943',
                'iso_code' => 'MZN',
                'name' => 'Mozambique Metical',
                'symbol' => 'MT',
                'exponent' => '2',
            ),
        'NAD' =>
            array (
                'active' => 'Y',
                'iso_num' => '516',
                'iso_code' => 'NAD',
                'name' => 'Namibia Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'NPR' =>
            array (
                'active' => 'Y',
                'iso_num' => '524',
                'iso_code' => 'NPR',
                'name' => 'Nepal Rupee',
                'symbol' => '₨',
                'exponent' => '2',
            ),
        'ANG' =>
            array (
                'active' => 'Y',
                'iso_num' => '532',
                'iso_code' => 'ANG',
                'name' => 'Netherlands Antilles Guilder',
                'symbol' => 'ƒ',
                'exponent' => '2',
            ),
        'NZD' =>
            array (
                'active' => 'Y',
                'iso_num' => '554',
                'iso_code' => 'NZD',
                'name' => 'New Zealand Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'NIO' =>
            array (
                'active' => 'Y',
                'iso_num' => '558',
                'iso_code' => 'NIO',
                'name' => 'Nicaragua Cordoba',
                'symbol' => 'C$',
                'exponent' => '2',
            ),
        'NGN' =>
            array (
                'active' => 'Y',
                'iso_num' => '566',
                'iso_code' => 'NGN',
                'name' => 'Nigeria Naira',
                'symbol' => '₦',
                'exponent' => '2',
            ),
        'OMR' =>
            array (
                'active' => 'Y',
                'iso_num' => '512',
                'iso_code' => 'OMR',
                'name' => 'Oman Rial',
                'symbol' => '﷼',
                'exponent' => '3',
            ),
        'PKR' =>
            array (
                'active' => 'Y',
                'iso_num' => '586',
                'iso_code' => 'PKR',
                'name' => 'Pakistan Rupee',
                'symbol' => '₨',
                'exponent' => '2',
            ),
        'PAB' =>
            array (
                'active' => 'Y',
                'iso_num' => '590',
                'iso_code' => 'PAB',
                'name' => 'Panama Balboa',
                'symbol' => 'B/.',
                'exponent' => '2',
            ),
        'PYG' =>
            array (
                'active' => 'Y',
                'iso_num' => '600',
                'iso_code' => 'PYG',
                'name' => 'Paraguay Guarani',
                'symbol' => 'Gs',
                'exponent' => '0',
            ),
        'PEN' =>
            array (
                'active' => 'Y',
                'iso_num' => '604',
                'iso_code' => 'PEN',
                'name' => 'Peru Nuevo Sol',
                'symbol' => 'S/.',
                'exponent' => '2',
            ),
        'PHP' =>
            array (
                'active' => 'Y',
                'iso_num' => '608',
                'iso_code' => 'PHP',
                'name' => 'Philippines Peso',
                'symbol' => 'Php',
                'exponent' => '2',
            ),
        'PLN' =>
            array (
                'active' => 'Y',
                'iso_num' => '985',
                'iso_code' => 'PLN',
                'name' => 'Poland Zloty',
                'symbol' => 'zł',
                'exponent' => '2',
            ),
        'QAR' =>
            array (
                'active' => 'Y',
                'iso_num' => '634',
                'iso_code' => 'QAR',
                'name' => 'Qatar Riyal',
                'symbol' => '﷼',
                'exponent' => '2',
            ),
        'RON' =>
            array (
                'active' => 'Y',
                'iso_num' => '946',
                'iso_code' => 'RON',
                'name' => 'Romania New Leu',
                'symbol' => 'lei',
                'exponent' => '2',
            ),
        'RUB' =>
            array (
                'active' => 'Y',
                'iso_num' => '643',
                'iso_code' => 'RUB',
                'name' => 'Russia Ruble',
                'symbol' => 'руб',
                'exponent' => '2',
            ),
        'SHP' =>
            array (
                'active' => 'Y',
                'iso_num' => '654',
                'iso_code' => 'SHP',
                'name' => 'Saint Helena Pound',
                'symbol' => '£',
                'exponent' => '2',
            ),
        'SAR' =>
            array (
                'active' => 'Y',
                'iso_num' => '682',
                'iso_code' => 'SAR',
                'name' => 'Saudi Arabia Riyal',
                'symbol' => '﷼',
                'exponent' => '2',
            ),
        'SCR' =>
            array (
                'active' => 'Y',
                'iso_num' => '690',
                'iso_code' => 'SCR',
                'name' => 'Seychelles Rupee',
                'symbol' => '₨',
                'exponent' => '2',
            ),
        'SBD' =>
            array (
                'active' => 'Y',
                'iso_num' => '090',
                'iso_code' => 'SBD',
                'name' => 'Solomon Islands Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'SOS' =>
            array (
                'active' => 'Y',
                'iso_num' => '706',
                'iso_code' => 'SOS',
                'name' => 'Somalia Shilling',
                'symbol' => 'S',
                'exponent' => '2',
            ),
        'ZAR' =>
            array (
                'active' => 'Y',
                'iso_num' => '710',
                'iso_code' => 'ZAR',
                'name' => 'South Africa Rand',
                'symbol' => 'R',
                'exponent' => '2',
            ),
        'LKR' =>
            array (
                'active' => 'Y',
                'iso_num' => '144',
                'iso_code' => 'LKR',
                'name' => 'Sri Lanka Rupee',
                'symbol' => '₨',
                'exponent' => '2',
            ),
        'SYP' =>
            array (
                'active' => 'Y',
                'iso_num' => '760',
                'iso_code' => 'SYP',
                'name' => 'Syria Pound',
                'symbol' => '£',
                'exponent' => '2',
            ),
        'TWD' =>
            array (
                'active' => 'Y',
                'iso_num' => '901',
                'iso_code' => 'TWD',
                'name' => 'Taiwan New Dollar',
                'symbol' => 'NT$',
                'exponent' => '2',
            ),
        'THB' =>
            array (
                'active' => 'Y',
                'iso_num' => '764',
                'iso_code' => 'THB',
                'name' => 'Thailand Baht',
                'symbol' => '฿',
                'exponent' => '2',
            ),
        'TTD' =>
            array (
                'active' => 'Y',
                'iso_num' => '780',
                'iso_code' => 'TTD',
                'name' => 'Trinidad and Tobago Dollar',
                'symbol' => 'TT$',
                'exponent' => '2',
            ),
        'TRY' =>
            array (
                'active' => 'Y',
                'iso_num' => '949',
                'iso_code' => 'TRY',
                'name' => 'Turkey Lira',
                'symbol' => 'YTL',
                'exponent' => '2',
            ),
        'UAH' =>
            array (
                'active' => 'Y',
                'iso_num' => '980',
                'iso_code' => 'UAH',
                'name' => 'Ukraine Hryvna',
                'symbol' => '₴',
                'exponent' => '2',
            ),
        'UYU' =>
            array (
                'active' => 'Y',
                'iso_num' => '858',
                'iso_code' => 'UYU',
                'name' => 'Uruguay Peso',
                'symbol' => '$U',
                'exponent' => '2',
            ),
        'UZS' =>
            array (
                'active' => 'Y',
                'iso_num' => '860',
                'iso_code' => 'UZS',
                'name' => 'Uzbekistan Som',
                'symbol' => 'лв',
                'exponent' => '2',
            ),
        'VEF' =>
            array (
                'active' => 'Y',
                'iso_num' => '937',
                'iso_code' => 'VEF',
                'name' => 'Venezuela Bolivar Fuerte',
                'symbol' => 'Bs',
                'exponent' => '2',
            ),
        'VND' =>
            array (
                'active' => 'Y',
                'iso_num' => '704',
                'iso_code' => 'VND',
                'name' => 'Viet Nam Dong',
                'symbol' => '₫',
                'exponent' => '0',
            ),
        'YER' =>
            array (
                'active' => 'Y',
                'iso_num' => '886',
                'iso_code' => 'YER',
                'name' => 'Yemen Rial',
                'symbol' => '﷼',
                'exponent' => '2',
            ),
        'WST' =>
            array (
                'active' => 'Y',
                'iso_num' => '882',
                'iso_code' => 'WST',
                'name' => 'Samoa Tala',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'STD' =>
            array (
                'active' => 'Y',
                'iso_num' => '678',
                'iso_code' => 'STD',
                'name' => 'São Tomé and Príncipe Dobra',
                'symbol' => 'Db',
                'exponent' => '2',
            ),
        'RWF' =>
            array (
                'active' => 'Y',
                'iso_num' => '646',
                'iso_code' => 'RWF',
                'name' => 'Rwanda Franc',
                'symbol' => 'RF',
                'exponent' => '0',
            ),
        'CHW' =>
            array (
                'active' => 'Y',
                'iso_num' => '948',
                'iso_code' => 'CHW',
                'name' => 'WIR Franc',
                'symbol' => 'CHW',
                'exponent' => '2',
            ),
        'USN' =>
            array (
                'active' => 'Y',
                'iso_num' => '997',
                'iso_code' => 'USN',
                'name' => 'US Dollar (Next day)',
                'symbol' => 'USN',
                'exponent' => '2',
            ),
        'AED' =>
            array (
                'active' => 'Y',
                'iso_num' => '784',
                'iso_code' => 'AED',
                'name' => 'United Arab Emirates Dirham',
                'symbol' => 'د.إ',
                'exponent' => '2',
            ),
        'UGX' =>
            array (
                'active' => 'Y',
                'iso_num' => '800',
                'iso_code' => 'UGX',
                'name' => 'Uganda Shilling',
                'symbol' => ' UGX',
                'exponent' => '2',
            ),
        'TMT' =>
            array (
                'active' => 'Y',
                'iso_num' => '934',
                'iso_code' => 'TMT',
                'name' => 'Turkmenistan Manat',
                'symbol' => 'TMT',
                'exponent' => '2',
            ),
        'USS' =>
            array (
                'active' => 'Y',
                'iso_num' => '998',
                'iso_code' => 'USS',
                'name' => 'US Dollar (Same day)',
                'symbol' => 'USS',
                'exponent' => '2',
            ),
        'UYI' =>
            array (
                'active' => 'Y',
                'iso_num' => '940',
                'iso_code' => 'UYI',
                'name' => 'Uruguay Peso en Unidades Indexadas (URUIURUI)',
                'symbol' => 'UYI',
                'exponent' => '0',
            ),
        'ZWL' =>
            array (
                'active' => 'Y',
                'iso_num' => '932',
                'iso_code' => 'ZWL',
                'name' => 'Zimbabwe Dollar',
                'symbol' => 'Z$',
                'exponent' => '2',
            ),
        'ZMK' =>
            array (
                'active' => 'Y',
                'iso_num' => '894',
                'iso_code' => 'ZMK',
                'name' => 'Zambia Kwacha',
                'symbol' => 'Zk',
                'exponent' => '2',
            ),
        'VUV' =>
            array (
                'active' => 'Y',
                'iso_num' => '548',
                'iso_code' => 'VUV',
                'name' => 'Vanuatu Vatu',
                'symbol' => 'VT',
                'exponent' => '0',
            ),
        'TND' =>
            array (
                'active' => 'Y',
                'iso_num' => '788',
                'iso_code' => 'TND',
                'name' => 'Tunisia Dinar',
                'symbol' => 'د.ت',
                'exponent' => '3',
            ),
        'TOP' =>
            array (
                'active' => 'Y',
                'iso_num' => '776',
                'iso_code' => 'TOP',
                'name' => 'Tonga Pa\'anga',
                'symbol' => 'T$',
                'exponent' => '2',
            ),
        'SDG' =>
            array (
                'active' => 'Y',
                'iso_num' => '938',
                'iso_code' => 'SDG',
                'name' => 'Sudan Pound',
                'symbol' => 'SDG',
                'exponent' => '2',
            ),
        'SSP' =>
            array (
                'active' => 'Y',
                'iso_num' => '728',
                'iso_code' => 'SSP',
                'name' => 'South Sudanese Pound',
                'symbol' => 'SSP',
                'exponent' => '2',
            ),
        'XSU' =>
            array (
                'active' => 'Y',
                'iso_num' => '994',
                'iso_code' => 'XSU',
                'name' => 'Sucre',
                'symbol' => 'XSU',
                'exponent' => '\\N',
            ),
        'SLL' =>
            array (
                'active' => 'Y',
                'iso_num' => '694',
                'iso_code' => 'SLL',
                'name' => 'Sierra Leone Leone',
                'symbol' => 'Le',
                'exponent' => '2',
            ),
        'SRD' =>
            array (
                'active' => 'Y',
                'iso_num' => '968',
                'iso_code' => 'SRD',
                'name' => 'Suriname Dollar',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'SZL' =>
            array (
                'active' => 'Y',
                'iso_num' => '748',
                'iso_code' => 'SZL',
                'name' => 'Swaziland Lilangeni',
                'symbol' => 'SZL',
                'exponent' => '2',
            ),
        'TZS' =>
            array (
                'active' => 'Y',
                'iso_num' => '834',
                'iso_code' => 'TZS',
                'name' => 'Tanzania Shilling',
                'symbol' => 'TSh',
                'exponent' => '2',
            ),
        'TJS' =>
            array (
                'active' => 'Y',
                'iso_num' => '972',
                'iso_code' => 'TJS',
                'name' => 'Tajikistan Somoni',
                'symbol' => 'TJS',
                'exponent' => '2',
            ),
        'CHE' =>
            array (
                'active' => 'Y',
                'iso_num' => '947',
                'iso_code' => 'CHE',
                'name' => 'WIR Euro',
                'symbol' => 'CHE',
                'exponent' => '2',
            ),
        'RSD' =>
            array (
                'active' => 'Y',
                'iso_num' => '941',
                'iso_code' => 'RSD',
                'name' => 'Serbia Dinar',
                'symbol' => 'РСД',
                'exponent' => '2',
            ),
        'KWD' =>
            array (
                'active' => 'Y',
                'iso_num' => '414',
                'iso_code' => 'KWD',
                'name' => 'Kuwait Dinar',
                'symbol' => 'ك',
                'exponent' => '3',
            ),
        'COU' =>
            array (
                'active' => 'Y',
                'iso_num' => '970',
                'iso_code' => 'COU',
                'name' => 'Unidad de Valor Real',
                'symbol' => 'COU',
                'exponent' => '2',
            ),
        'CLF' =>
            array (
                'active' => 'Y',
                'iso_num' => '990',
                'iso_code' => 'CLF',
                'name' => 'Unidades de fomento',
                'symbol' => 'UF',
                'exponent' => '0',
            ),
        'CVE' =>
            array (
                'active' => 'Y',
                'iso_num' => '132',
                'iso_code' => 'CVE',
                'name' => 'Cape Verde Escudo',
                'symbol' => '$',
                'exponent' => '2',
            ),
        'XAF' =>
            array (
                'active' => 'Y',
                'iso_num' => '950',
                'iso_code' => 'XAF',
                'name' => 'Communauté Financière Africaine (BEAC) CFA Franc B',
                'symbol' => 'XAF',
                'exponent' => '0',
            ),
        'KMF' =>
            array (
                'active' => 'Y',
                'iso_num' => '174',
                'iso_code' => 'KMF',
                'name' => 'Comoros Franc',
                'symbol' => 'KMF',
                'exponent' => '0',
            ),
        'CDF' =>
            array (
                'active' => 'Y',
                'iso_num' => '976',
                'iso_code' => 'CDF',
                'name' => 'Congo/Kinshasa Franc',
                'symbol' => 'FC',
                'exponent' => '2',
            ),
        'ETB' =>
            array (
                'active' => 'Y',
                'iso_num' => '230',
                'iso_code' => 'ETB',
                'name' => 'Ethiopia Birr',
                'symbol' => 'Br',
                'exponent' => '2',
            ),
        'ERN' =>
            array (
                'active' => 'Y',
                'iso_num' => '232',
                'iso_code' => 'ERN',
                'name' => 'Eritrea Nakfa',
                'symbol' => 'ERN',
                'exponent' => '2',
            ),
        'DJF' =>
            array (
                'active' => 'Y',
                'iso_num' => '262',
                'iso_code' => 'DJF',
                'name' => 'Djibouti Franc',
                'symbol' => 'DJF',
                'exponent' => '0',
            ),
        'CUC' =>
            array (
                'active' => 'Y',
                'iso_num' => '931',
                'iso_code' => 'CUC',
                'name' => 'Cuba Convertible Peso',
                'symbol' => 'CUC$',
                'exponent' => '2',
            ),
        'BIF' =>
            array (
                'active' => 'Y',
                'iso_num' => '108',
                'iso_code' => 'BIF',
                'name' => 'Burundi Franc',
                'symbol' => 'FBu',
                'exponent' => '0',
            ),
        'BOV' =>
            array (
                'active' => 'Y',
                'iso_num' => '984',
                'iso_code' => 'BOV',
                'name' => 'Mvdol',
                'symbol' => 'Bs',
                'exponent' => '2',
            ),
        'AWG' =>
            array (
                'active' => 'Y',
                'iso_num' => '533',
                'iso_code' => 'AWG',
                'name' => 'Aruba Guilder',
                'symbol' => 'ƒ',
                'exponent' => '2',
            ),
        'AMD' =>
            array (
                'active' => 'Y',
                'iso_num' => '051',
                'iso_code' => 'AMD',
                'name' => 'Armenia Dram',
                'symbol' => '֏',
                'exponent' => '2',
            ),
        'AOA' =>
            array (
                'active' => 'Y',
                'iso_num' => '973',
                'iso_code' => 'AOA',
                'name' => 'Angola Kwanza',
                'symbol' => 'Kz',
                'exponent' => '2',
            ),
        'DZD' =>
            array (
                'active' => 'Y',
                'iso_num' => '012',
                'iso_code' => 'DZD',
                'name' => 'Algeria Dinar',
                'symbol' => 'دج',
                'exponent' => '2',
            ),
        'AZN' =>
            array (
                'active' => 'Y',
                'iso_num' => '944',
                'iso_code' => 'AZN',
                'name' => 'Azerbaijan New Manat',
                'symbol' => 'ман',
                'exponent' => '2',
            ),
        'BHD' =>
            array (
                'active' => 'Y',
                'iso_num' => '048',
                'iso_code' => 'BHD',
                'name' => 'Bahrain Dinar',
                'symbol' => 'BD',
                'exponent' => '3',
            ),
        'BTN' =>
            array (
                'active' => 'Y',
                'iso_num' => '064',
                'iso_code' => 'BTN',
                'name' => 'Bhutan Ngultrum',
                'symbol' => 'Nu',
                'exponent' => '2',
            ),
        'XOF' =>
            array (
                'active' => 'Y',
                'iso_num' => '952',
                'iso_code' => 'XOF',
                'name' => 'Communauté Financière Africaine (BCEAO) Franc',
                'symbol' => 'XOF',
                'exponent' => '0',
            ),
        'BDT' =>
            array (
                'active' => 'Y',
                'iso_num' => '050',
                'iso_code' => 'BDT',
                'name' => 'Bangladesh Taka',
                'symbol' => 'Tk',
                'exponent' => '2',
            ),
        'XPF' =>
            array (
                'active' => 'Y',
                'iso_num' => '953',
                'iso_code' => 'XPF',
                'name' => 'Comptoirs Français du Pacifique (CFP) Franc',
                'symbol' => 'XPF',
                'exponent' => '0',
            ),
        'GMD' =>
            array (
                'active' => 'Y',
                'iso_num' => '270',
                'iso_code' => 'GMD',
                'name' => 'Gambia Dalasi',
                'symbol' => 'D',
                'exponent' => '2',
            ),
        'MVR' =>
            array (
                'active' => 'Y',
                'iso_num' => '462',
                'iso_code' => 'MVR',
                'name' => 'Maldives (Maldive Islands) Rufiyaa',
                'symbol' => 'MRf',
                'exponent' => '2',
            ),
        'MWK' =>
            array (
                'active' => 'Y',
                'iso_num' => '454',
                'iso_code' => 'MWK',
                'name' => 'Malawi Kwacha',
                'symbol' => 'MK',
                'exponent' => '2',
            ),
        'MGA' =>
            array (
                'active' => 'Y',
                'iso_num' => '969',
                'iso_code' => 'MGA',
                'name' => 'Madagascar Ariary',
                'symbol' => 'Ar',
                'exponent' => '2',
            ),
        'MOP' =>
            array (
                'active' => 'Y',
                'iso_num' => '446',
                'iso_code' => 'MOP',
                'name' => 'Macau Pataca',
                'symbol' => 'MOP$',
                'exponent' => '2',
            ),
        'MRO' =>
            array (
                'active' => 'Y',
                'iso_num' => '478',
                'iso_code' => 'MRO',
                'name' => 'Mauritania Ouguiya',
                'symbol' => 'UM',
                'exponent' => '2',
            ),
        'XUA' =>
            array (
                'active' => 'N',
                'iso_num' => '965',
                'iso_code' => 'XUA',
                'name' => 'ADB Unit of Account',
                'symbol' => 'XAU',
                'exponent' => '\\N',
            ),
        'MMK' =>
            array (
                'active' => 'Y',
                'iso_num' => '104',
                'iso_code' => 'MMK',
                'name' => 'Myanmar (Burma) Kyat',
                'symbol' => 'K',
                'exponent' => '2',
            ),
        'MAD' =>
            array (
                'active' => 'Y',
                'iso_num' => '504',
                'iso_code' => 'MAD',
                'name' => 'Morocco Dirham',
                'symbol' => 'د.م',
                'exponent' => '2',
            ),
        'MDL' =>
            array (
                'active' => 'Y',
                'iso_num' => '498',
                'iso_code' => 'MDL',
                'name' => 'Moldova Leu',
                'symbol' => 'MDL',
                'exponent' => '2',
            ),
        'MXV' =>
            array (
                'active' => 'Y',
                'iso_num' => '979',
                'iso_code' => 'MXV',
                'name' => 'Mexican Unidad de Inversion (UDI)',
                'symbol' => 'MXV',
                'exponent' => '2',
            ),
        'LYD' =>
            array (
                'active' => 'Y',
                'iso_num' => '434',
                'iso_code' => 'LYD',
                'name' => 'Libya Dinar',
                'symbol' => 'LD',
                'exponent' => '3',
            ),
        'LSL' =>
            array (
                'active' => 'Y',
                'iso_num' => '426',
                'iso_code' => 'LSL',
                'name' => 'Lesotho Loti',
                'symbol' => 'L',
                'exponent' => '2',
            ),
        'HTG' =>
            array (
                'active' => 'Y',
                'iso_num' => '332',
                'iso_code' => 'HTG',
                'name' => 'Haiti Gourde',
                'symbol' => 'G',
                'exponent' => '2',
            ),
        'GNF' =>
            array (
                'active' => 'Y',
                'iso_num' => '324',
                'iso_code' => 'GNF',
                'name' => 'Guinea Franc',
                'symbol' => 'FG',
                'exponent' => '0',
            ),
        'GHS' =>
            array (
                'active' => 'Y',
                'iso_num' => '936',
                'iso_code' => 'GHS',
                'name' => 'Ghana Cedi',
                'symbol' => 'GH¢',
                'exponent' => '2',
            ),
        'GEL' =>
            array (
                'active' => 'Y',
                'iso_num' => '981',
                'iso_code' => 'GEL',
                'name' => 'Georgia Lari',
                'symbol' => 'GEL',
                'exponent' => '2',
            ),
        'XDR' =>
            array (
                'active' => 'Y',
                'iso_num' => '960',
                'iso_code' => 'XDR',
                'name' => 'International Monetary Fund (IMF) Special Drawing ',
                'symbol' => 'XDR',
                'exponent' => '\\N',
            ),
        'IQD' =>
            array (
                'active' => 'Y',
                'iso_num' => '368',
                'iso_code' => 'IQD',
                'name' => 'Iraq Dinar',
                'symbol' => 'د.ع',
                'exponent' => '3',
            ),
        'AFN' =>
            array (
                'active' => 'Y',
                'iso_num' => '971',
                'iso_code' => 'AFN',
                'name' => 'Afghanistan Afghani',
                'symbol' => '؋',
                'exponent' => '2',
            ),
        'KES' =>
            array (
                'active' => 'Y',
                'iso_num' => '404',
                'iso_code' => 'KES',
                'name' => 'Kenya Shilling',
                'symbol' => 'KSh',
                'exponent' => '2',
            ),
        'JOD' =>
            array (
                'active' => 'Y',
                'iso_num' => '400',
                'iso_code' => 'JOD',
                'name' => 'Jordan Dinar',
                'symbol' => 'JD',
                'exponent' => '3',
            ),
        'PGK' =>
            array (
                'active' => 'Y',
                'iso_num' => '598',
                'iso_code' => 'PGK',
                'name' => 'Papua New Guinea Kina',
                'symbol' => 'K',
                'exponent' => '2',
            ),
    );

    public static function calculateAmountByCurrency($amount, string $currencyCode): int
    {
        if (!isset(self::AVAILABLE_CURRENCIES[$currencyCode])) {
            throw new \Exception(sprintf('Currency %s, is not supported for amount calculation', $currencyCode));
        }

        $currency = self::AVAILABLE_CURRENCIES[$currencyCode];

        if ('N' === $currency['active']) {
            throw new \Exception(sprintf('Currency %s, is not supported for amount calculation', $currencyCode));
        }

        return (int) bcmul(round($amount, $currency['exponent']), pow(10, $currency['exponent']));
    }
}
