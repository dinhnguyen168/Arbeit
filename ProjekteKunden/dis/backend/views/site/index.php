<?php
/* @var $this yii\web\View */
$this->title = 'DIS';
?>
<style>
    * {
        box-sizing: border-box;
        background-repeat: no-repeat;
        padding: 0;
        margin: 0;
    }
    .startup-loading {
        background: #000;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .drill-bit {
        perspective: 200px;
        font-size: 50px;
        width: 1.15em;
        height: 1.15em;
        border-radius: 50%;
        position: relative;
        box-shadow: inset 0 0 0 0.01em #aaa;
        color: #fff;
    }
    .drill-1, .drill-2, .drill-3 {
        width: 0;
        height: 0;
        border-radius: 50%;
        border: solid 0.01em transparent;
        position: absolute;
        left: 0.57em;
        top: 0.57em;
        box-shadow: 0 -0.26em 0 0.05em, 0.18em -0.18em 0 0.05em, 0.25em 0 0 0.05em, 0.175em 0.175em 0 0.05em, 0 0.25em 0 0.05em, -0.18em 0.18em 0 0.05em, -0.26em 0 0 0.05em, -0.18em -0.18em 0 0.05em, -0.08em -0.08em 0 0.03em, 0.08em -0.08em 0 0.03em, -0.08em 0.08em 0 0.03em, 0.08em 0.08em 0 0.03em;
    }
    .drill-1 {
        text-indent: -9999em;
        animation: drill1 2s infinite linear;
    }
    .drill-2 {
        animation: drill2 2s infinite linear;
    }
    .drill-3 {
        animation: drill3 2s infinite linear;
    }
    @keyframes drill1 {
        0% {
            transform: rotate(120deg) translate(0.3em, 0) rotateY(-50deg) rotateZ(0);
        }
        100% {
            transform: rotate(120deg) translate(0.3em, 0) rotateY(-50deg) rotateZ(360deg);
        }
    }
    @keyframes drill2 {
        0% {
            transform: rotate(240deg) translate(0.3em, 0) rotateY(-50deg) rotateZ(0);
        }
        100% {
            transform: rotate(240deg) translate(0.3em, 0) rotateY(-50deg) rotateZ(360deg);
        }
    }
    @keyframes drill3 {
        0% {
            transform: rotate(0deg) translate(0.3em, 0) rotateY(-50deg) rotateZ(0);
        }
        100% {
            transform: rotate(0deg) translate(0.3em, 0) rotateY(-50deg) rotateZ(360deg);
        }
    }

</style>

<div class="startup-loading">
    <div class="drill-bit">
        <div class="drill-1"></div>
        <div class="drill-2"></div>
        <div class="drill-3"></div>
    </div>
</div>