<nav class="menu">
    <ul>
        <li class="<?php echo $vars['active']==='overview' ? 'active':''; ?>">
            <a href="javascript:window.location.search=''">Overview</a>
        </li>
        <li class="<?php echo $vars['active']==='assessments' ? 'active':''; ?>">
            <a href="javascript:window.location.search='page=assessments'">Assessments</a>
        </li>
        <li class="<?php echo $vars['active']==='preferences' ? 'active':''; ?>">
            <a href="javascript:window.location.search='page=preferences'">Preferences</a>
        </li>
    </ul>
</nav>
