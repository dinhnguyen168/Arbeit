<?php


namespace app\reports\traits;


use Mpdf\Mpdf;

trait DirectPrintTrait
{
    /**
     * @param $return_var int result of exec command
     * @param $command string executed command
     * @param $pdf Mpdf pdf file object
     *
     * @return string as a response
     */
    function formatDirectPrintResult ($return_var, $command, $pdf) {
        \Yii::$app->controller->layout = "main";
        return $this->renderString($return_var == 0 ? $this->getSuccessTemplate() : $this->getErrorTemplate(), ['return_var' => $return_var, 'command' => $command, 'pdf' => $pdf]);
    }

    private function getSuccessTemplate () {
        return <<<'EOD'
<div class="container" style="padding-top: 60px;">
<div class="panel panel-success">
    <div class="panel-heading">
      Print Command Sent Successfully    
    </div>
      <div class="panel-body">
        <a class="btn btn-primary pull-right" download="report.pdf" href="data:application/pdf;base64,<?= base64_encode($pdf->Output('', 'S')) ?>">
          PDF Report <i class="glyphicon glyphicon-download"></i>
        </a>
        <p>The command: <code><?= $command ?></code> returned <?= $return_var ?></p>
        <p>
          If the printer did'nt respond to the command, download the pdf report and print it manually. 
        </p>
      </div>
    </div>
</div>
EOD;

    }

    private function getErrorTemplate () {
        return <<<'EOD'
<div class="container" style="padding-top: 60px;">
<div class="panel panel-danger">
    <div class="panel-heading">
      Print Command Error    
    </div>
      <div class="panel-body">
        <a class="btn btn-primary pull-right" download="report.pdf" href="data:application/pdf;base64,<?= base64_encode($pdf->Output('', 'S')) ?>">
          PDF Report <i class="glyphicon glyphicon-download"></i>
        </a>
        <p>The command: <code><?= $command ?></code> returned <?= $return_var ?></p>
        <p>
          Please contact system administrator. Alternatively, you can download the generated report using the button on 
          the right and print it manually. 
        </p>
      </div>
    </div>
</div>
EOD;
    }


}
