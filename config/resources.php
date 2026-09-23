<?php
return [
    'categories'=>['singular'=>'category','label'=>'Categories','description'=>'Organize a question bank around any subject.','columns'=>['name'=>'Subject','description'=>'Description','questions_count'=>'Questions','is_active'=>'Active']],
    'questions'=>['singular'=>'question','label'=>'Question bank','description'=>'Create clear questions. Keep the answer key on the server.','columns'=>['prompt'=>'Question','category.name'=>'Subject','difficulty'=>'Difficulty','is_active'=>'Active']],
    'quizzes'=>['singular'=>'quiz','label'=>'Quiz studio','description'=>'Turn your question bank into focused assessments.','columns'=>['title'=>'Quiz','category.name'=>'Subject','question_count'=>'Questions','duration_minutes'=>'Minutes','is_published'=>'Published']],
    'users'=>['singular'=>'account','label'=>'People','description'=>'Create accounts, manage access, and choose a learning experience.','columns'=>['name'=>'Name','email'=>'Email','role'=>'Role','theme_mode'=>'Theme mode','is_active'=>'Active']],
    'themes'=>['singular'=>'theme','label'=>'Appearance','description'=>'Six starting palettes. Three layouts. A personal experience for every learner.','columns'=>['name'=>'Theme','layout'=>'Layout','accent'=>'Accent','is_dark'=>'Dark','is_active'=>'Active']],
];
