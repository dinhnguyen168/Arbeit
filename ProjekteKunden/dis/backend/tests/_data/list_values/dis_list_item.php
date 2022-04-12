<?php

$longListItems = [];
for ($i = 1; $i <= 200; $i++) {
    $longListItems[] = [/* 'id' => 30000 + $i, */"list_id" => 4, "display" => "LL$i", "remark" => "Long List Item $i", "uri" => null, "sort" => 0];
}

return array_merge([
        [ /* 'id' => 581, */"list_id" => 1, "display" => "B", "remark" => "Bit Sample", "uri" => null, "sort" => 200],
        [ /* 'id' => 582, */"list_id" => 1, "display" => "H", "remark" => "Advanced Piston Corer (APC)", "uri" => null, "sort" => 20],
        [ /* 'id' => 583, */"list_id" => 1, "display" => "L", "remark" => "Rumohr Lot / Core", "uri" => null, "sort" => 40],
        [ /* 'id' => 584, */"list_id" => 1, "display" => "M", "remark" => "Miscellaneous", "uri" => null, "sort" => 210],
        [ /* 'id' => 585, */"list_id" => 1, "display" => "N", "remark" => "Non-rotating Core Barrel", "uri" => null, "sort" => 140],
        [ /* 'id' => 586, */"list_id" => 1, "display" => "O", "remark" => "Non-coring Assembly/Open Hole", "uri" => null, "sort" => 120],
        [ /* 'id' => 587, */"list_id" => 1, "display" => "P", "remark" => "Push Coring Assembly", "uri" => null, "sort" => 150],
        [ /* 'id' => 588, */"list_id" => 1, "display" => "R", "remark" => "Rotary Core Barrel (RCB)", "uri" => null, "sort" => 230],
        [ /* 'id' => 589, */"list_id" => 1, "display" => "S", "remark" => "Hammer Sampler", "uri" => null, "sort" => 130],
        [ /* 'id' => 590, */"list_id" => 1, "display" => "T", "remark" => "Tripple Tube Core Barrel", "uri" => null, "sort" => 130],
        [ /* 'id' => 591, */"list_id" => 1, "display" => "W", "remark" => "Wash Core Sample", "uri" => null, "sort" => 250],
        [ /* 'id' => 592, */"list_id" => 1, "display" => "X", "remark" => "Extended Core Barrel (XCB)", "uri" => null, "sort" => 30],
        [ /* 'id' => 593, */"list_id" => 1, "display" => "Z", "remark" => "Advanced Diamond Core Barrel (ADCB)", "uri" => null, "sort" => 260],
        [/* 'id' => 21750, */"list_id" => 2, "display" => "CK", "remark" => "Cindy Kunkel", "uri" => null, "sort" => 1],
        [/* 'id' => 21751, */"list_id" => 2, "display" => "KH", "remark" => "Katja Heeschen", "uri" => null, "sort" => 1],
        [/* 'id' => 21752, */"list_id" => 2, "display" => "KB", "remark" => "Knut Behrends", "uri" => null, "sort" => 1],
        [/* 'id' => 25014, */"list_id" => 2, "display" => "TG", "remark" => "Thomas Gibson", "uri" => null, "sort" => 0],
        [/* 'id' => 25017, */"list_id" => 2, "display" => "SPH", "remark" => "Steve Hesselbo", "uri" => null, "sort" => 0],
        [/* 'id' => 30000, */"list_id" => 3, "display" => "SPH", "remark" => "Steve Hesselbo", "uri" => null, "sort" => 0],
    ], $longListItems);