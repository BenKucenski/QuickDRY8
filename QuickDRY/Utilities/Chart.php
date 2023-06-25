<?php

namespace QuickDRY\Utilities;


use QuickDRY\Utilities\strongType;

class Chart extends strongType
{
    public float $width;
    public float $height;
    public string $im;
    public string $title;
    public string $cur_color;
    public float $cur_x;
    public float $cur_y;
    public string $cur_font;

    public float $chart_x;
    public float $chart_y;
    public float $chart_width;
    public float $chart_height;

    public function __construct($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->im = imagecreatetruecolor($width, $height);
        imagefill($this->im, 0, 0, $this->GetColor(255, 255, 255));
    }

    public function SetChart($x, $y, $width, $height)
    {
        $this->chart_x = $x;
        $this->chart_y = $y;
        $this->chart_width = $width;
        $this->chart_height = $height;
    }

    public function SetStartPoint($x, $y)
    {
        $this->cur_x = $x;
        $this->cur_y = $this->height - $y;
    }

    public function SetStartPointRatio($x, $y)
    {
        $x = floor($x * $this->width);
        $y = floor($y * $this->height);
        $this->SetStartPoint($x, $y);
    }

    public function PlotPointRatio($x, $y, $width, $height = null)
    {
        if (is_null($height))
            $height = $width;

        $x = floor($x * $this->width);
        $y = floor((1.0 - $y) * $this->height);
        imagearc($this->im, $x, $y, $width, $height, 0, 360, $this->cur_color);
    }

    public function PlotChartPointRatio($x, $y, $width, $height = null)
    {
        if (is_null($height))
            $height = $width;

        $x = floor($x * $this->chart_width) + $this->chart_x;
        $y = floor((1.0 - $y) * $this->chart_height) + $this->chart_y;
        imagearc($this->im, $x, $y, $width, $height, 0, 360, $this->cur_color);
    }

    public function LineToRatio($x, $y)
    {
        $x = floor($x * $this->width);
        $y = floor((1.0 - $y) * $this->height);

        imageline($this->im, $x, $y, $this->cur_x, $this->cur_y, $this->cur_color);
    }

    public function SetColor($r, $g, $b)
    {
        $this->cur_color = $this->GetColor($r, $g, $b);
    }

    public function SetFont($x)
    {
        $this->cur_font = $x;
    }

    public function WriteRatio($str, $x, $y)
    {
        $x = floor($x * $this->width);
        $y = floor((1.0 - $y) * $this->height);
        imagestring($this->im, $this->cur_font, $x, $y, $str, $this->cur_color);
    }

    public function WriteChartRatio($str, $x, $y)
    {
        $x = floor($x * $this->chart_width) + $this->chart_x;
        $y = floor((1.0 - $y) * $this->chart_height) + $this->chart_y;
        imagestring($this->im, $this->cur_font, $x, $y, $str, $this->cur_color);
    }


    private function GetColor($r, $g, $b)
    {
        return imagecolorallocate($this->im, $r, $g, $b);
    }

    public function GetJpeg(): bool
    {
        imagestring($this->im, 5, 1, 1, strtoupper($this->title), $this->GetColor(0, 0, 0));

        return imagejpeg($this->im);
    }
}
