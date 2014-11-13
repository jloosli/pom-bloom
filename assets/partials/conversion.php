<h2>Converting from the old Bloom</h2>
<?php if (current_user_can('activate_plugins')): ?>
<form method="post">
    <input type="hidden" name="action" value="convert_goal_suggestions"/>
    <input type="submit" value="Import Old Bloom data"/>
</form>
<?php
    endif;
if(current_user_can('activate_plugins') && !empty($_POST) && !empty($_POST['action']) && $_POST['action'] = 'convert_goal_suggestions') {
    $this->removeAllPostTypes( 'bloom-assessments' );
    $this->removeAllPostTypes( 'bloom_suggested' );
    $this->removeAllPostTypes( 'bloom-user-goals' );
    $this->removeCategories('bloom-categories');
    $this->removeCategories('bloom-goalsets');

    $categories = $this->getOldBloomCategories();
    $this->addCategories( $categories );
    $this->addAssessmentQuestions();
    $this->addSuggestions();
    $this->addAssessments();
    $this->addGoalsets();
    $this->addGoals();

    echo "<h2>Running...</h2>";
//$this->addPastAssessments();
//$this->addPastGoals();
}
