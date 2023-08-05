<?php
# PHP Calendar (version 2.3), written by Keith Devens
# http://keithdevens.com/software/php_calendar
#  see example at http://keithdevens.com/weblog
# License: http://keithdevens.com/software/license
namespace QuickDRY\Utilities;

/**
 *
 */
class HTMLCalendar
{


    /**
     * @param       $year
     * @param       $month
     * @param array $days
     * @param array $pn
     * @param int $day_name_length
     * @param null $month_href
     * @param int $first_day
     *
     * @return string
     */
    public static function Generate($year, $month, array $days = [], array $pn = [], int $day_name_length = 3, $month_href = NULL, int $first_day = 0): string
    {
        $first_of_month = gmmktime(0, 0, 0, $month, 1, $year);
        #remember that mktime will automatically correct if invalid dates are entered
        # for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
        # this provides a built in "rounding" feature to generate_calendar()

        $day_names = []; #generate all the day names according to the current locale
        for ($n = 0, $t = (3 + $first_day) * 86400; $n < 7; $n++, $t += 86400) #January 4, 1970 was a Sunday
            $day_names[$n] = ucfirst(gmstrftime('%A', $t)); #%A means full textual day name

        list($month, $year, $month_name, $weekday) = explode(',', gmstrftime('%m,%Y,%B,%w', $first_of_month));
        $weekday = ((int)$weekday + 7 - $first_day) % 7; #adjust for $first_day
        $title = htmlentities(ucfirst($month_name)) . '&nbsp;' . $year;  #note that some locales don't capitalize month and day names

        #Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
        $keys = array_keys($pn);
        $p = $keys[0];
        $pl = $pn[$p];
        $n = $keys[1];
        $nl = $pn[$n];
        if ($p) $p = '<span class="calendar-prev">' . ($pl ? '<a href="' . htmlspecialchars($pl) . '">' . $p . '</a>' : $p) . '</span>&nbsp;';
        if ($n) $n = '&nbsp;<span class="calendar-next">' . ($nl ? '<a href="' . htmlspecialchars($nl) . '">' . $n . '</a>' : $n) . '</span>';
        $calendar = '<table class="calendar">' . "\n" .
            '<caption class="calendar-month">' . $p . ($month_href ? '<a href="' . htmlspecialchars($month_href) . '">' . $title . '</a>' : $title) . $n . "</caption>\n<tr>";

        if ($day_name_length) { #if the day names should be shown ($day_name_length > 0)
            #if day_name_length is >3, the full name of the day will be printed
            foreach ($day_names as $d)
                $calendar .= '<th class="calendar_day_name" abbr="' . htmlentities($d) . '">' . htmlentities($day_name_length < 4 ? substr($d, 0, $day_name_length) : $d) . '</th>';
            $calendar .= "</tr>\n<tr>";
        }

        if ($weekday > 0) $calendar .= '<td colspan="' . $weekday . '">&nbsp;</td>'; #initial 'empty' days
        for ($day = 1, $days_in_month = gmdate('t', $first_of_month); $day <= $days_in_month; $day++, $weekday++) {
            if ($weekday == 7) {
                $weekday = 0; #start a new week
                $calendar .= "</tr>\n<tr>";
            }
            if (isset($days[$day]) && is_array($days[$day])) {
                if (isset($days[$day]['content'])) {
                    $link = $days[$day]['link'] ?? null;
                    $classes = $days[$day]['classes'] ?? null;
                    $content = $days[$day]['content'];

                    $calendar .= '
					<td class="calendar_day ' . ($classes ? htmlspecialchars($classes) : '') . ' droppable" id="cal_' . Dates::Datestamp($month . '/' . $day . '/' . $year) . '">
					<div id="month_date" rel="' . Dates::Datestamp($month . '/' . $day . '/' . $year) . '" class="day_num">' . $day . '</div>
					<div class="day_content">' . ($link ? '<a href="' . htmlspecialchars($link) . '">' . $content . '</a>' : $content) . '</div>
					</td>';
                } else {
                    $calendar .= '
					<td class="calendar_day droppable" id="cal_' . Dates::Datestamp($month . '/' . $day . '/' . $year) . '">
					<div id="month_date" rel="' . Dates::Datestamp($month . '/' . $day . '/' . $year) . '" class="day_num">' . $day . '</div>
					<div class="day_content">
				';

                    foreach ($days[$day] as $day_info) {
                        $link = $day_info['link'] ?? null;
                        //$classes = isset($day_info['classes']) ? $day_info['classes'] : null;
                        $content = $day_info['content'] ?? null;

                        $calendar .= ($link ? '<a href="' . htmlspecialchars($link) . '">' . $content . '</a>' : $content);
                    }
                    $calendar .= '</div></td>';
                }
            } else $calendar .= '<td class="calendar_day droppable" id="cal_' . Dates::Datestamp($month . '/' . $day . '/' . $year) . '"><div rel="' . Dates::Datestamp($month . '/' . $day . '/' . $year) . '" class="day_num">' . $day . '</div></td>';
        }
        if ($weekday != 7) $calendar .= '<td colspan="' . (7 - $weekday) . '">&nbsp;</td>'; #remaining "empty" days

        return $calendar . "</tr>\n</table>\n";
    }
}