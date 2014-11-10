<h2>Set your goals</h2>
<div class="instructions">
    Follow these simple steps: (click here to minimize)

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
<form>
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
            Here is your average for this category: 4.2
        </div>
        <div class="recommendations">
            <h3>Recommendations</h3>
            Select a category from the box above to get recommendations.
        </div>
        <div class="goal">
            <h3>Goal</h3>
            Click on the recommendation and it will appear here. Use it as is, edit it, or just make up your own goal.
        </div>
        <div class="per_week">
            <h3>Times per week</h3>
            Set how many times per week you want to do this goal.<br>
            <select>
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