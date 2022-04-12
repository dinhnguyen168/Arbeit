<?php


namespace app\modules\api\common\actions;


use app\models\ProjectExpedition;
use yii\base\Action;

class getMessagesAction extends Action
{

    public function run()
    {
        $messages = [];

        if (\Yii::$app->user->can('sa')) {
            $projectExpedition = new ProjectExpedition();
            if ($projectExpedition->hasAttribute('moratorium_end')) {
                $now = new \DateTime("today");
                $nextWeek = new \DateTime("today");
                $nextWeek->modify('+1 week');
                foreach (\app\models\ProjectExpedition::find()
                    ->andWhere(['>=', 'moratorium_end', $now->format('Y-m-d')])
                    ->andWhere(['<=', 'moratorium_end', $nextWeek->format('Y-m-d')])
                    ->all() as $expedition) {
                    $endDate = new \DateTime($expedition->moratorium_end);
                    $diff = $endDate->diff($now);
                    $days = $diff->d;
                    $messages[] = ["type" => "warning", "text" => "Moratorium for expedition '" . $expedition->name . "' (" . $expedition->exp_acronym . ") ends " . ($days == 0 ? "today." : ($days == 1 ? "tomorrow." : "in $days days."))];
                }
            }
        }
        return $messages;
    }

}
