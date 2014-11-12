<h2>Update Goals</h2>
<div class="instructions">
    <p>If your goal is hidden, you can hover over the box to see the full text. When you complete an action
        related to your goal, check the appropriate box. (You can "uncheck" a checked box by re-clicking it.)
        A check will appear in the "Done" column once you've checked off the number of actions you chose for that
        goal.</p>

    <p>If you need a little help understanding the "Serendipity Moments," you can hover over those boxes for
        examples.</p>
</div>

<table id="goals_update">
    <thead>
    <tr>
        <th>Goal</th>
        <?php foreach($dow as $day): ?>
        <th><?php echo ucfirst($day); ?></th>
        <?php endforeach; ?>
        <th>Done</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ( $goals as $goal ): ?>
        <tr class="goal">
            <?php if ( is_null( $goal->category ) ) :
                $is_serendipity = true; ?>
                <th><label for="serendipity_<?php echo $goal->ID; ?>">Serendipity Moment</label></th>
                <td colspan="7">
                    <textarea rows="2" id="serendipity_<?php echo $goal->ID; ?>"
                              name="serendipity_<?php echo $goal->ID; ?>"
                              data-id="<?php echo $goal->ID; ?>"
                              class="serendipity"
                        ><?php echo $goal->post_title; ?></textarea>
                </td>
            <?php else:
                $is_serendipity = false;?>
                <th><div><?php echo $goal->post_title; ?></div></th>
                <?php foreach($dow as $day): ?>
                    <td data-day="<?php echo $day; ?>" data-goal="<?php echo $goal->ID; ?>"
                        class="day_progress clickable <?php echo empty($goal->completed[$day])?'':'set'; ?>">&nbsp;</td>
                <?php endforeach; ?>
            <?php endif; ?>
            <td data-goal="<?php echo $goal->ID; ?>" data-per_week="<?php echo $goal->per_week; ?>"
                class="done <?php
                echo ($is_serendipity && strlen($goal->post_title) ||
                      (!$is_serendipity && array_sum($goal->completed) >= $goal->per_week)) ?
                    "set": "";
                ?>">&nbsp;</td>
        </tr>
    <?php endforeach; ?>
    </tbody>

</table>
