<?php
{
    { # init - #todo don't rely on DbViewer
        include("../db_viewer/init.php");
        $requestVars = array_merge($_GET, $_POST);

        function getGlobalFields2omit() {
            return array(
                "iid","id",
                "time","time_added",
                "tags", #todo make tags ok
                "stars",
            );
        }

        $schemas_in_path = DbViewer::schemas_in_path($search_path);
        $schemas_val_list = DbViewer::val_list_str($schemas_in_path);
    }

    { #vars
        $table = isset($requestVars['table'])
                    ? $requestVars['table']
                    : null;

        { 
            if ($table) { # get fields
                $dbRowsReFields = Util::sql("
                    select
                        table_schema, table_name, column_name
                    from information_schema.columns
                    where table_name='$table'
                        and table_schema in ($schemas_val_list)
                "
                    #todo fix issue where redundant tables in multiple schemas
                        # lead to redundant fields
                );
                $fields = array_map(
                    function($x) {
                        return $x['column_name'];
                    },
                    $dbRowsReFields
                );

                { # fields2omit
                    $fields2omit = getGlobalFields2omit();

                    switch ($table) {
                        case "example_table": {
                            $tblFields2skip = array(
                                "iid","id","time",
                                "tags", #todo
                            );
                        } break;

                        default:
                            $tblFields2omit = array("iid","id","time");
                    }

                    $fields2omit = array_merge($fields2omit, $tblFields2omit);

                    { # allow addition of more omitted fields
                        $omit = isset($requestVars['omit'])
                                    ? $requestVars['omit']
                                    : null;
                        $omitted_fields = explode(',', $omit);
                        $fields2omit = array_merge($fields2omit, $omitted_fields);
                    }

                    { # allow addition of more kept fields
                        $keep = isset($requestVars['keep'])
                                    ? $requestVars['keep']
                                    : null;
                        $kept_fields = explode(',', $keep);
                        $fields2keep = $kept_fields;
                    }
                }
            }
        }
    }
}
?>




<html>
    <head>
        <title>Dash</title>
        <style type="text/css">
body {
    font-family: sans-serif;
    margin: 3em;
}
<?php /* # $background='dark'
?>
body {
    background: black;
    color: white;
}
a {
    color: yellow;
}
<?php */
?>
form#mainForm {
}
form#mainForm label {
    min-width: 8rem;
    display: inline-block;
    vertical-align: middle;
}
.formInput {
    margin: 2rem auto;
}

.formInput input,
.formInput textarea
{
    width: 30rem;
    display: inline-block;
    vertical-align: middle;
}

#whoami {
    font-size: 80%;
}

#table_header > * {
    display: inline-block;
    vertical-align: middle;
    margin: .5rem;
}
        </style>
        <script>
        function setFormAction(url) {
            var form = document.getElementById('mainForm');
            form.action = url;
        }
        </script>
    </head>
    <body>
<?php
    {
        if ($table) {
?>
        <p id="whoami">Dash</p>
        <div id="table_header">
            <h1>
                <code><?= $table ?></code> table
            </h1>
            <a href="/db_viewer/db_viewer.php?sql=select * from <?= $table ?>"
               target="_blank"
            >
                view all
            </a>
        </div>

        <!-- action gets set via js -->
        <form id="mainForm" target="_blank">
<?php
            foreach ($fields as $name) {
                if (in_array($name, $fields2omit)
                    && !in_array($name, $fields2keep)
                ) {
                    continue;
                }
                $inputTag = ($name == "txt"
                                ? "textarea"
                                : "input");
?>
            <div class="formInput" remove="true">
                <label for="<?= $name ?>">
                    <?= $name ?>
                </label>
                <<?= $inputTag ?> name="<?= $name ?>"><?= "</$inputTag>" ?>
            </div>
<?php
            }
?>
            <div id="submits">
                <input onclick="setFormAction('/ormrouter/create_<?= $table ?>')" value="Create" type="submit" />
                <input onclick="setFormAction('/ormrouter/update_<?= $table ?>')" value="Update" type="submit" />
                <input onclick="setFormAction('/ormrouter/view_<?= $table ?>')" value="View" type="submit" />
                <input onclick="setFormAction('/ormrouter/delete_<?= $table ?>')" value="Delete" type="submit" />
            </div>
        </form>
<?php
        }
        else {
            include("choose_table.php");
        }
    }
?>
    </body>
</html>
