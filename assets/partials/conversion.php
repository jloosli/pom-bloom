<h2>Converting from the old Bloom</h2>
<form method="post">
    <input type="hidden" name="action" value="convert_goal_suggestions"/>
    <input type="submit" value="Import Old Bloom data"/>
</form>
<?php
if(!empty($_POST) && !empty($_POST['action']) && $_POST['action'] = 'convert_goal_suggestions') {
    $this->removeAllPostTypes( 'bloom-assessments' );
    $this->removeAllPostTypes( 'bloom_suggested' );
    $this->removeAllPostTypes( 'bloom-user-goals' );
    $this->removeCategories('bloom-categories');

    $categories = $this->getOldBloomCategories();
    $this->addCategories( $categories );
    $this->addAssessmentQuestions();
    $this->addSuggestions();

//$this->addPastAssessments();
//$this->addPastGoals();
}
