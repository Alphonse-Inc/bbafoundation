<?php

declare(strict_types=1);

/**
 * Editable suggestions. This is not an exhaustive directory of Nigerian tertiary institutions.
 * ownership government = federal or state. Private institutions are included so an application
 * can be flagged; they are not presented as eligible.
 */
$governmentUniversities = [
    'University of Ibadan',
    'University of Lagos',
    'Obafemi Awolowo University',
    'University of Nigeria, Nsukka',
    'Ahmadu Bello University',
    'University of Benin',
    'University of Ilorin',
    'University of Jos',
    'University of Calabar',
    'University of Port Harcourt',
    'University of Maiduguri',
    'Bayero University, Kano',
    'Usmanu Danfodiyo University',
    'University of Uyo',
    'University of Abuja',
    'Nnamdi Azikiwe University',
    'Federal University of Technology, Akure',
    'Federal University of Technology, Minna',
    'Federal University of Technology, Owerri',
    'Federal University Oye-Ekiti',
    'Federal University of Agriculture, Abeokuta',
    'Federal University, Lokoja',
    'Federal University, Lafia',
    'Federal University, Dutse',
    'Federal University, Dutsin-Ma',
    'Federal University, Kashere',
    'Federal University, Wukari',
    'Federal University, Birnin Kebbi',
    'Federal University, Gusau',
    'Federal University, Gashua',
    'Alex Ekwueme Federal University, Ndufu-Alike',
    'Michael Okpara University of Agriculture, Umudike',
    'Modibbo Adama University, Yola',
    'Ekiti State University',
    'Bamidele Olumilua University of Education, Science and Technology, Ikere-Ekiti',
    'Lagos State University',
    'Lagos State University of Science and Technology',
    'Adekunle Ajasin University',
    'Olusegun Agagu University of Science and Technology',
    'Osun State University',
    'Ladoke Akintola University of Technology',
    'Olabisi Onabanjo University',
    'Tai Solarin University of Education',
    'Ambrose Alli University',
    'Delta State University',
    'Rivers State University',
    'Kaduna State University',
    'Nasarawa State University',
    'Benue State University',
    'Prince Abubakar Audu University',
    'Kwara State University',
    'Ibrahim Badamasi Babangida University',
    'Gombe State University',
    'Yobe State University',
    'Sokoto State University',
    'Plateau State University',
    'Taraba State University',
    'Adamawa State University',
    'Bauchi State University',
    'Ebonyi State University',
    'Enugu State University of Science and Technology',
    'Imo State University',
    'Abia State University',
    'Akwa Ibom State University',
    'University of Cross River State',
    'Chukwuemeka Odumegwu Ojukwu University',
    'First Technical University, Ibadan',
];

$governmentPolytechnics = [
    'Yaba College of Technology',
    'Federal Polytechnic, Ado-Ekiti',
    'Federal Polytechnic, Ilaro',
    'Federal Polytechnic, Offa',
    'Federal Polytechnic, Ede',
    'Federal Polytechnic, Nekede',
    'Federal Polytechnic, Bida',
    'Federal Polytechnic, Idah',
    'Federal Polytechnic, Kaura Namoda',
    'Federal Polytechnic, Mubi',
    'Federal Polytechnic, Nasarawa',
    'Federal Polytechnic, Oko',
    'Federal Polytechnic, Bauchi',
    'Federal Polytechnic, Damaturu',
    'Federal Polytechnic, Ile-Oluji',
    'Auchi Polytechnic',
    'Kaduna Polytechnic',
    'The Polytechnic, Ibadan',
    'Rufus Giwa Polytechnic',
    'Moshood Abiola Polytechnic',
    'Ekiti State Polytechnic, Isan-Ekiti',
    'Osun State Polytechnic, Iree',
    'Kwara State Polytechnic',
    'Kogi State Polytechnic',
    'Lagos State Polytechnic',
];

$privateUniversities = [
    'Covenant University',
    'Babcock University',
    'Afe Babalola University',
    'American University of Nigeria',
    'Bowen University',
    "Redeemer's University",
    'Lead City University',
    'Bells University of Technology',
    'Caleb University',
    'Pan-Atlantic University',
];

$rows = [];
foreach ($governmentUniversities as $name) {
    $rows[] = ['name' => $name, 'institution_type' => 'university', 'ownership' => 'government'];
}
foreach ($governmentPolytechnics as $name) {
    $rows[] = ['name' => $name, 'institution_type' => 'polytechnic', 'ownership' => 'government'];
}
foreach ($privateUniversities as $name) {
    $rows[] = ['name' => $name, 'institution_type' => 'university', 'ownership' => 'private'];
}

return $rows;
