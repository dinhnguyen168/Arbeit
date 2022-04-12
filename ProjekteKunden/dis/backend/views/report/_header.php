<h1><?= $big_title?></h1>

<table class="header" cellspacing="0">
    <tr>
        <td style="width:33%;text-align: left">DIS: Data-Report</td>
        <td style="width:34%;text-align: center"><?= $title?></td>
        <td style="width:33%;text-align: right"></td>
    </tr>
    <tr>
        <td colspan="3">
            <table class="extra" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="big">
                        <u>Expedition:</u> <?= $expedition_id ?>
                    </td>
                    <td class="big">
                        <u>Site:</u> <?= $site_id ?>
                    </td>
                    <td class="big">
                        <u>Hole:</u> <?= $hole_id ?>
                    </td>
                    <td class="normal">
                        <?php foreach ($info_list as $label => $value): ?>
                            <span style="font-size: 3mm; font-weight: normal; white-space: nowrap;"><?= $label?>:<?= $value?></span>&nbsp;&nbsp;&nbsp;
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>