<?php $assessment_total = count( $assessments );

foreach ( $hierarchy as $h ): ?>
    <tr>
        <th class="category-level"><?php echo $h['name']; ?></th>
        <?php for ( $i = 0; $i <= $assessment_total; $i ++ ): ?>
            <td>&nbsp;</td>
        <?php endfor; ?>
    </tr>
    <?php if ( !empty( $h['questions'] ) && $h['questions'] ):
        foreach ( $h['questions'] as $q ): ?>
            <tr>
                <td><?php echo $q->post_title; ?></td>
                <?php $average = [0,0]; foreach($this->getAssessmentResponses($q, $assessments) as $r): ?>
                    <td><?php $rating = end( $r )['rating'];
                        if($rating === 0) {
                            echo "N/A";
                        } else {
                            echo $rating;
                            $average[0] += $rating; // sum
                            $average[1]++; // count
                        }?></td>
                <?php endforeach; ?>
                <th><?php echo $average[1] > 0? round($average[0]/$average[1],1): "N/A"; ?></th>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if(!empty($h['sections']) && $h['sections']) {
            echo $this->get_partial('assessments.summary.table', ['assessments' => $assessments,'hierarchy'=>$h['sections']]);
    } ?>
<?php endforeach; ?>
