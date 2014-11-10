<h2>Bloom Self Assessment Scores</h2>
<div class="instructions">
    As a reminder, here's what your scores mean.<br>
    1 = Help!<br>
    2 = Not so great<br>
    3 = Okay<br>
    4 = Pretty good<br>
    5 = Wonderful<br>
    <br>
    When you set your goals, you'll probably want to focus on the areas where you scored between 1 and 3.
    Don't worry. We all have a ways to go in many areas!
</div>

<table class="assessment_results">
    <thead>
    <tr>
        <th>Categories and Questions</th>
        <?php foreach ( $assessments as $a ): ?>
            <th><?php echo date("m/d/y",strtotime($a['assessment_date'])); ?></th>
        <?php endforeach; ?>
        <th>Average</th>
    </tr>
    </thead>
    <tbody>
    <?php echo $this->get_partial('assessments.summary.table', compact(['assessments','hierarchy'])); ?>
<!--    --><?php //$assessment_total = count( $assessments );
//
//    foreach ( $hierarchy as $h ): ?>
<!--        <tr>-->
<!--            <th class="category-level">--><?php //echo $h['name']; ?><!--</th>-->
<!--            --><?php //for ( $i = 0; $i <= $assessment_total; $i ++ ): ?>
<!--                <td>&nbsp;</td>-->
<!--            --><?php //endfor; ?>
<!--        </tr>-->
<!--        --><?php //if ( !empty( $h['questions'] ) && $h['questions'] ):
//            foreach ( $h['questions'] as $q ): ?>
<!--                <tr>-->
<!--                    <td>--><?php //echo $q->post_title; ?><!--</td>-->
<!--                    --><?php //$average = [0,0]; foreach($this->getAssessmentResponses($q, $assessments) as $r): ?>
<!--                    <td>--><?php //$rating = end( $r )['rating'];
//                        if($rating === 0) {
//                            echo "N/A";
//                        } else {
//                            echo $rating;
//                            $average[0] += $rating; // sum
//                            $average[1]++; // count
//                        }?><!--</td>-->
<!--                    --><?php //endforeach; ?>
<!--                    <th>--><?php //echo $average[1] > 0? round($average[0]/$average[1],1): "N/A"; ?><!--</th>-->
<!--                </tr>-->
<!--            --><?php //endforeach; ?>
<!--        --><?php //endif; ?>
<!--    --><?php //endforeach; ?>
    </tbody>
</table>

<pre>
<?php

print_r( $meta );
?>
</pre>

