<?php

function covid19ImpactEstimator($data)
{
  $impactRate = 10;
  $severeRate = 50;
  $days = periodToDays($data['periodType'], $data['timeToElapse']);
  $dDays = 3;

  $factor = ceil($days / $dDays);
  
  // Calculate currentlyInfected
  $iCurrentlyInfected = intval($data['reportedCases']) * $impactRate ?? 0;
  $sCurrentlyInfected = intval($data['reportedCases']) * $severeRate ?? 0;
  // End Calculate currentlyInfected
  
  // Calculate infectionsByRequestedTime
  $iInfectionsByRequestedTime = $iCurrentlyInfected * (pow(2, $factor));
  $sInfectionsByRequestedTime = $sCurrentlyInfected * (pow(2, $factor));
  // End Calculate infectionsByRequestedTime
  
  // Compute 15% of infectionsByRequestedTime
  $ibrt_percent = 15/100;
  $iSevereCasesByRequestedTime = $ibrt_percent * $iInfectionsByRequestedTime;
  $sSevereCasesByRequestedTime = $ibrt_percent * $sInfectionsByRequestedTime;
  // End Compute 15% of infectionsByRequestedTime

  // Compute Bed By Request
  $totalBeds = intval($data["totalHospitalBeds"]);
  $expectedPercentage = 35/100;
  $expectedBed = ceil($totalBeds * $expectedPercentage);
  
  $iHospitalBedsByRequestedTime = $expectedBed - $iSevereCasesByRequestedTime;
  $sHospitalBedsByRequestedTime = $expectedBed - $sSevereCasesByRequestedTime;
  // End Compute Bed By Request


  // Compute Case for ICU
  $iTimePercent_5 = 5/100;
  $iCasesForICUByRequestedTime = $iInfectionsByRequestedTime * $iTimePercent_5;
  $sCasesForICUByRequestedTime = $sInfectionsByRequestedTime * $iTimePercent_5;
  // End Compute Case for ICU

  
  // Compute Case for Ventilators
  $iTimePercent_2 = 2/100;
  $iCasesForVentilatorsByRequestedTime = $iInfectionsByRequestedTime * $iTimePercent_2;
  $sCasesForVentilatorsByRequestedTime = $sInfectionsByRequestedTime * $iTimePercent_2;
  // End Compute Case for Ventilators


  // Compute Dollars In Flight
  $incomePopulation = $data["region"]["avgDailyIncomePopulation"];
  $avgUSDIncome = $data["region"]["avgDailyIncomeInUSD"];
  $population = $data["population"];
  
  $iDollarsInFlight = ($iInfectionsByRequestedTime * $incomePopulation) * $avgUSDIncome * $days ;
  $sDollarsInFlight = ($sInfectionsByRequestedTime * $incomePopulation) * $avgUSDIncome * $days ;
  // End Compute Dollars In Flight

  $impact = [
    "currentlyInfected" => $iCurrentlyInfected,
    "infectionsByRequestedTime" => $iInfectionsByRequestedTime,
    "severeCasesByRequestedTime" => $iSevereCasesByRequestedTime,
    "hospitalBedsByRequestedTime" => $iHospitalBedsByRequestedTime,
    "casesForICUByRequestedTime" => $iCasesForICUByRequestedTime,
    "casesForVentilatorsByRequestedTime" => $iCasesForVentilatorsByRequestedTime,
    "dollarsInFlight" => $iDollarsInFlight
  ];
  
  $severeImpact = [
    "currentlyInfected" => $sCurrentlyInfected,
    "infectionsByRequestedTime" => $sInfectionsByRequestedTime,
    "severeCasesByRequestedTime" => $sSevereCasesByRequestedTime,
    "hospitalBedsByRequestedTime" => $sHospitalBedsByRequestedTime,
    "casesForICUByRequestedTime" => $sCasesForICUByRequestedTime,
    "casesForVentilatorsByRequestedTime" => $sCasesForVentilatorsByRequestedTime,
    "dollarsInFlight" => $sDollarsInFlight
  ];

  return compact(
    "data",
    "impact",
    "severeImpact"
  );
}

function periodToDays($periodType, $timeToElapse)
{
  $days = 0;

  switch ($periodType) {
    case "days":
      $days = $timeToElapse;
      break;
    case "weeks":
      $days = 7 * $timeToElapse;
      break;
    case "months":
      $days = 30 * $timeToElapse;
      break;
    case "years":
      $days = 365 * $timeToElapse;
      break;
    default:
      break;
  }

  return $days;
}

// print_r(
//   covid19ImpactEstimator([
//     "region" => [
//       "name" => "Africa",
//       "avgAge" => 19.7,
//       "avgDailyIncomeInUSD" => 5,
//       "avgDailyIncomePopulation" => 0.71
//     ],
//     "periodType" => "days",
//     "timeToElapse" => 30,
//     "reportedCases" => 674,
//     "population" => 66622705,
//     "totalHospitalBeds" => 1380614
//   ])
// );