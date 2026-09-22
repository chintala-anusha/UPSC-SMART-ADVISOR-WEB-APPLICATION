<?php
/* ---------------------------------------------------------------
   quiz_generator.php
   Generates a UPSC-style MCQ set.
   Replace the sample bank below with real PYQ-parsed questions
   or hook into an AI API endpoint.
--------------------------------------------------------------- */

function generate_quiz(int $count = 100, string $year = '', string $type = 'prelims'): array {

    /* ----------------------------------------------------------
       SAMPLE QUESTION BANK
       Structure: [ question, options[A-D], answer, subject, year ]
       Add / replace with your real PYQ database queries here.
    ---------------------------------------------------------- */
    $bank = [
        ['q'=>'Which Article of the Indian Constitution abolishes untouchability?','o'=>['A'=>'Article 14','B'=>'Article 17','C'=>'Article 21','D'=>'Article 25'],'a'=>'B','s'=>'Polity','y'=>2019],
        ['q'=>'The "Doctrine of Lapse" was introduced by which Governor General?','o'=>['A'=>'Lord Cornwallis','B'=>'Lord Wellesley','C'=>'Lord Dalhousie','D'=>'Lord Canning'],'a'=>'C','s'=>'History','y'=>2018],
        ['q'=>'Which planet in our solar system has the most natural satellites?','o'=>['A'=>'Jupiter','B'=>'Saturn','C'=>'Uranus','D'=>'Neptune'],'a'=>'B','s'=>'Science','y'=>2020],
        ['q'=>'The Palk Strait separates India from which country?','o'=>['A'=>'Maldives','B'=>'Myanmar','C'=>'Sri Lanka','D'=>'Bangladesh'],'a'=>'C','s'=>'Geography','y'=>2017],
        ['q'=>'Which of the following is NOT a Fundamental Right under the Indian Constitution?','o'=>['A'=>'Right to Equality','B'=>'Right to Property','C'=>'Right to Freedom','D'=>'Right against Exploitation'],'a'=>'B','s'=>'Polity','y'=>2016],
        ['q'=>'The term "Stagflation" refers to a situation of:','o'=>['A'=>'High inflation and high growth','B'=>'Low inflation and low growth','C'=>'High inflation and low growth','D'=>'Low inflation and high growth'],'a'=>'C','s'=>'Economy','y'=>2015],
        ['q'=>'Which Mughal emperor built the Taj Mahal?','o'=>['A'=>'Akbar','B'=>'Jahangir','C'=>'Shah Jahan','D'=>'Aurangzeb'],'a'=>'C','s'=>'History','y'=>2014],
        ['q'=>'The Chipko Movement is associated with:','o'=>['A'=>'Soil conservation','B'=>'Forest conservation','C'=>'Water conservation','D'=>'Wildlife conservation'],'a'=>'B','s'=>'Environment','y'=>2013],
        ['q'=>'Which Five-Year Plan gave priority to poverty alleviation?','o'=>['A'=>'Third Plan','B'=>'Fourth Plan','C'=>'Fifth Plan','D'=>'Sixth Plan'],'a'=>'C','s'=>'Economy','y'=>2012],
        ['q'=>'Who among the following gave the "Drain of Wealth" theory?','o'=>['A'=>'Mahatma Gandhi','B'=>'Dadabhai Naoroji','C'=>'Bal Gangadhar Tilak','D'=>'Gopal Krishna Gokhale'],'a'=>'B','s'=>'History','y'=>2011],
        ['q'=>'The Strait of Hormuz connects the Persian Gulf to which body of water?','o'=>['A'=>'Red Sea','B'=>'Arabian Sea','C'=>'Gulf of Oman','D'=>'Bay of Bengal'],'a'=>'C','s'=>'Geography','y'=>2019],
        ['q'=>'Which Schedule of the Indian Constitution deals with anti-defection laws?','o'=>['A'=>'Seventh Schedule','B'=>'Eighth Schedule','C'=>'Ninth Schedule','D'=>'Tenth Schedule'],'a'=>'D','s'=>'Polity','y'=>2018],
        ['q'=>'The Nobel Prize in Economics is officially called:','o'=>['A'=>'Nobel Memorial Prize','B'=>'Sveriges Riksbank Prize','C'=>'Bank of Sweden Prize','D'=>'Alfred Nobel Prize'],'a'=>'B','s'=>'Economy','y'=>2020],
        ['q'=>'Kaziranga National Park is famous for:','o'=>['A'=>'Bengal Tiger','B'=>'Asiatic Lion','C'=>'One-horned Rhinoceros','D'=>'Snow Leopard'],'a'=>'C','s'=>'Environment','y'=>2017],
        ['q'=>'The Bhakra-Nangal Dam is built on which river?','o'=>['A'=>'Ravi','B'=>'Beas','C'=>'Sutlej','D'=>'Chenab'],'a'=>'C','s'=>'Geography','y'=>2016],
        ['q'=>'Which gas is primarily responsible for the greenhouse effect?','o'=>['A'=>'Oxygen','B'=>'Nitrogen','C'=>'Carbon Dioxide','D'=>'Hydrogen'],'a'=>'C','s'=>'Environment','y'=>2015],
        ['q'=>'The term "Fourth Estate" refers to:','o'=>['A'=>'Judiciary','B'=>'Legislature','C'=>'Executive','D'=>'Press/Media'],'a'=>'D','s'=>'Polity','y'=>2014],
        ['q'=>'Which Directive Principle is described as the "soul of the Constitution"?','o'=>['A'=>'Article 39','B'=>'Article 44','C'=>'Article 45','D'=>'Article 48'],'a'=>'A','s'=>'Polity','y'=>2013],
        ['q'=>'The battle of Plassey was fought in which year?','o'=>['A'=>'1757','B'=>'1761','C'=>'1764','D'=>'1774'],'a'=>'A','s'=>'History','y'=>2012],
        ['q'=>'Which Indian state has the longest coastline?','o'=>['A'=>'Tamil Nadu','B'=>'Andhra Pradesh','C'=>'Maharashtra','D'=>'Gujarat'],'a'=>'D','s'=>'Geography','y'=>2011],
        ['q'=>'The concept of "Judicial Review" in India was borrowed from:','o'=>['A'=>'UK','B'=>'Canada','C'=>'USA','D'=>'Australia'],'a'=>'C','s'=>'Polity','y'=>2019],
        ['q'=>'Which of the following is a non-renewable source of energy?','o'=>['A'=>'Solar energy','B'=>'Wind energy','C'=>'Coal','D'=>'Tidal energy'],'a'=>'C','s'=>'Science','y'=>2018],
        ['q'=>'The "Green Revolution" in India was associated with which crop?','o'=>['A'=>'Rice','B'=>'Wheat','C'=>'Maize','D'=>'Sugarcane'],'a'=>'B','s'=>'Economy','y'=>2017],
        ['q'=>'Who was the first woman President of India?','o'=>['A'=>'Indira Gandhi','B'=>'Sonia Gandhi','C'=>'Pratibha Patil','D'=>'Sarojini Naidu'],'a'=>'C','s'=>'Polity','y'=>2016],
        ['q'=>'The Tropic of Cancer does NOT pass through which Indian state?','o'=>['A'=>'Rajasthan','B'=>'Madhya Pradesh','C'=>'Gujarat','D'=>'Karnataka'],'a'=>'D','s'=>'Geography','y'=>2015],
    ];

    // If year filter is set, filter bank
    if ($year) {
        $filtered = array_filter($bank, fn($q) => $q['y'] == (int)$year);
        if (!empty($filtered)) $bank = array_values($filtered);
    }

    // Shuffle and slice to requested count
    shuffle($bank);
    $questions = array_slice($bank, 0, min($count, count($bank)));

    // Re-index
    return array_values($questions);
}