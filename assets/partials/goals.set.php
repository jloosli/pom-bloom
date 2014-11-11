<h2>Set your goals</h2>
<div class="instructions">
    <h3>Follow these simple steps:</h3>

    <ol>
        <li>In each of the main category boxes below, choose a subcategory and a list of recommended goals will pop up.
            You'll set one goal "For You," one goal "For Your Family," and one goal for "Beyond" for the beginner level.
            For the advanced level, you'll set TWO goals "For You," TWO goals "For Your Family," and one goal for
            "Beyond." If you'd like to change your level at this time, go to your preferences page.
            </li>
    <li>You can click on one of the goals and it will appear in the "Goal" box below. You can use it as is, or
        edit it to meet your needs. If you do not want to use any of the recommended goals, you can simply type
        in your own. You will need to select a category for each goal that you make up. (You do this by
        highlighting your category choice in the category box.)
        </li>
    <li>Select the number of times each week that you will need to do an action related to that goal.
        (Some goals are once a week goals, while others involve repetition.) If you use one of the recommended goals,
        the recommended number of times will show up in this box, but you can change it if you want.
        </li>
    Start setting your goals and get ready to <strong>BLOOM!</strong>
</div>
<form class="goals_set" method="post">
    <input type="hidden" name="goalset" value="599" />
    <?php foreach($categories as $cat): ?>
    <fieldset>
        <legend>Category: {<?php echo $cat['name']; ?>} - (Goal #<?php echo $cat['goal_num']; ?>)</legend>
        <div class="subcategories">
            <h3>Subcategories</h3>
                <?php $subCategories = $this->getSubCategories( $cat['id'] ); ?>
                <select multiple size="<?php echo min(count($subCategories), 8);?>" >
                <?php foreach ( $subCategories as $sub ): ?>
                    <option value="<?php echo $sub['id']; ?>"><?php echo $sub['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="assessments">
            <h3>Assessments</h3>
            <div class="chart_div_<?php echo $cat['id']; ?>"></div>
            <p>Here&rsquo;s where you scored yourself on your last few assessments.</p>
            <script>
                window.theCharts = window.theCharts || {};
                window.theCharts[<?php echo $cat['id']; ?>] = {
                    location: "chart_div_<?php echo $cat['id']; ?>",
                    category: '<?php echo $cat['name']; ?>',
                    data: [
                        <?php foreach($this->getCategoryAverages($current_user->ID, $cat['id']) as $assessment=> $avg): ?>
                        {assessment: '<?php echo $assessment; ?>',
                    average: <?php echo $avg; ?>},
                <?php endforeach; ?>
                ]
                };
            </script>
        </div>
        <div class="recommendations">
            <h3>Recommendations</h3>
            <div class="results">
            Select a category from the box above to get recommendations.
            </div>
        </div>
        <div class="goal">
            <h3>Goal</h3>
            <p>Click on the recommendation and it will appear here. Use it as is, edit it, or just make up your own goal.</p>
            <input type="hidden" name="suggestions[]" value="x" />
            <input type="hidden" name="cat[]" value="<?php echo $cat['id']; ?>" />
            <textarea required rows="5" name="goals[]"></textarea>

        </div>
        <div class="per_week">
            <h3>Times per week</h3>
            Set how many times per week you want to do this goal.<br>
            <select name="per_week[]">
                <option>1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
                <option>5</option>
                <option>6</option>
                <option>7</option>
            </select>
        </div>
    </fieldset>
    <?php endforeach; ?>
    <input type="submit" value="Submit your goals" />
</form>