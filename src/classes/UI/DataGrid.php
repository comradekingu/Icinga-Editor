<?php

namespace Icinga\Editor\UI;

/**
 * Description of DBFDataGrid
 *
 * @author vitex
 */
class DataGrid extends \Ease\Html\TableTag
{
    /**
     * Extra filtr výsledků
     * @var string
     */
    public $select;

    /**
     * Výchozí nastavení sloupečků
     * @var array
     */
    public $defaultColProp = ['sortable' => true];

    /**
     * Nastavení
     * @var array
     */
    public $options       = [
        'method' => 'GET',
        'dataType' => 'json',
        'height' => 'auto',
        'width' => 'auto',
        'sortname' => 'id',
        'sortorder' => 'asc',
        'usepager' => true,
        'useRp' => true,
        'rp' => 20,
        'dblClickResize' => true,
        'showTableToggleBtn' => true,
        'add' => [],
        'edit' => [],
        'buttons' => [
            ['name' => 'CSV Export', 'bclass' => 'csvexport']
//        , array('name' => 'PDF Export', 'bclass' => 'pdfexport')
        ]
    ];
    public $addFormItems  = [['name' => 'action', 'value' => 'add', 'type' => 'hidden']];
    public $editFormItems = [['name' => 'action', 'value' => 'edit', 'type' => 'hidden']];

    /**
     * Objekt jehož data jsou zobrazována
     * @var IEcfg
     */
    public $dataSource = null;

    /**
     * Klik na řádku vede na editor záznamu
     * @var type
     */
    public $dblclk2edit = true;

    /**
     * Zdroj dat pro flexigrid
     *
     * @param string $name ID elementu
     * @param string $datasource URL
     * @param array $properties vlastnosti elementu
     */
    function __construct($name, $datasource, $properties = null)
    {
        $this->dataSource       = $datasource;
        $this->options['title'] = $name;
        $this->setTagID();

        $this->options['url']      = 'datasource.php?class='.urlencode(get_class($datasource));
        $this->options['sortname'] = $datasource->getMyKeyColumn();
        parent::__construct(null, $properties);
        \Ease\JQuery\Part::jQueryze($this);
        \Ease\Shared::webPage()->includeJavaScript('js/flexigrid.js');
        \Ease\Shared::webPage()->includeCSS('css/flexigrid.css');
        $this->setUpButtons();
        $this->setUpColumns();
    }

    function setUpButtons()
    {
        $this->addAddButton(_('Add'));
        $this->addEditButton(_('Edit'));
        $this->addDeleteButton(_('Delete'));
    }

    function setUpColumns()
    {
        foreach ($this->dataSource->useKeywords as $keyword => $type) {
            if (isset($this->dataSource->keywordsInfo[$keyword])) {
                if (!isset($this->dataSource->keywordsInfo[$keyword]['title']) || !strlen(trim($this->dataSource->keywordsInfo[$keyword]['title']))) {
                    $this->addStatusMessage(_('Title missing').' '.$this->dataSource->keyword.': '.$keyword,
                        'warning');
                    $this->dataSource->keywordsInfo[$keyword]['title'] = $keyword;
                }
                if (strstr($type, 'VARCHAR') || strstr($type, 'STRING') || strstr($type,
                        'TEXT') || strstr($type, 'SELECT') || strstr($type,
                        'PLATFORM') || strstr($type, 'IDLIST') || strstr($type,
                        'BOOL') || strstr($type, 'DATE')) {
                    $this->setColumn($keyword,
                        $this->dataSource->keywordsInfo[$keyword]['title'], true);
                } else {
                    $this->setColumn($keyword,
                        $this->dataSource->keywordsInfo[$keyword]['title'],
                        false);
                }
            }
        }
    }

    /**
     * Přidá tlačítko
     *
     * @param string $title Popisek tlačítka
     * @param string $class CSS třída tlačítka
     */
    function addButton($title, $class, $onpress = null)
    {
        if ($onpress) {
            $this->options['buttons'][] = ['name' => $title, 'bclass' => $class,
                'onpress: '.$onpress];
        } else {
            $this->options['buttons'][] = ['name' => $title, 'bclass' => $class];
        }
    }

    /**
     * Vloží přidávací tlačítko
     *
     * @param string $title  Nadpis gridu
     * @param string $target Url
     */
    function addAddButton($title, $target = null)
    {
        $show = false;
        if (is_null($target)) {
            $target = $this->options['url'];
        }
        $this->addButton($title, 'add', 'addRecord');
        $this->addJavaScript('function addRecord(com, grid) {
              $(location).attr(\'href\',\''.$this->dataSource->keyword.'.php\');
            }
        ', null, true);
    }

    /**
     * Vloží editační tlačítko
     *
     * @param type $title
     * @param type $target
     */
    function addSelectAllButton($title, $target = null)
    {
        $this->addButton($title, 'selectAll', 'selectAll');
        $this->addJavaScript('function selectAll(com, grid) {
                $(\'tr\', grid).each(function() {
                    $(this).click();
                });
}');
    }

    /**
     * Vloží editační tlačítko
     *
     * @param type $title
     * @param type $target
     */
    function addEditButton($title, $target = null)
    {
        $this->addButton($title, 'edit', 'editRecord');
        $this->addJavaScript('function editRecord(com, grid) {

        var numItems = $(\'.trSelected\').length
        if(numItems){
            if(numItems == 1) {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    $(location).attr(\'href\',\''.$this->dataSource->keyword.'.php?'.$this->dataSource->getMyKeyColumn().'=\' +id);
                });

            } else {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    var url =\''.$this->dataSource->keyword.'.php?'.$this->dataSource->getMyKeyColumn().'=\' +id;
                    var win = window.open(url, \'_blank\');
                    win.focus();
                });
            }
        } else {
            alert("'._('Please select any rows').'");
        }

            }
        ', null, true);
    }

    /**
     * Přidá tlačítko pro smazání záznamu
     *
     * @param string $title  popisek tlačítka
     * @param string $target výkonný skript
     */
    function addDeleteButton($title, $target = null)
    {
        if (is_null($target)) {
            $target = $this->options['url'];
        }
        $this->addButton($title, 'delete', 'deleteRecord');
        $this->addJavaScript('function deleteRecord(com, grid) {

        var numItems = $(\'.trSelected\').length
        if(numItems){
            if(numItems == 1) {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    $(location).attr(\'href\',\''.$this->dataSource->keyword.'.php?action=delete&'.$this->dataSource->getMyKeyColumn().'=\' +id);
                });

            } else {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    var url =\''.$this->dataSource->keyword.'.php?action=delete&'.$this->dataSource->getMyKeyColumn().'=\' +id;
                    var win = window.open(url, \'_blank\');
                    win.focus();
                });
            }
        } else {
            alert("'._('Please select any rows').'");
        }


            }
        ', null, true);
    }

    /**
     * Nastaví parametry sloupečky
     *
     * @param string  $name             jméno z databáze
     * @param string  $title            popisek sloupce
     * @param boolean $search           nabídnout pro sloupec vyhledávání
     * @param array   $columnProperties další vlastnosti v poli
     */
    function setColumn($name, $title, $search = false, $columnProperties = null)
    {
        if (!isset($this->options['colModel'])) {
            $this->options['colModel'] = [];
        }
        if (!isset($columnProperties['editable'])) {
            $columnProperties['editable'] = false;
        }
        $properties            = $this->defaultColProp;
        $properties['name']    = $name;
        $properties['display'] = $title;
        if (is_array($columnProperties)) {
            $this->options['colModel'][] = array_merge($columnProperties,
                $properties);
        } else {
            $this->options['colModel'][] = $properties;
        }
        if ($search) {
            if (is_array($search)) {
                foreach ($search as $sid => $srch) {
                    $search[$sid] .= ' LIKE "%"';
                }
                $search = implode(' OR ', $search);
            }
            $this->options['searchitems'][] = ['display' => $title, 'name' => $name,
                'where' => addslashes($search)];
        }

        if ($columnProperties['editable']) {
            if (!isset($columnProperties['label'])) {
                $columnProperties['label'] = $title;
            }
            if (!isset($columnProperties['value'])) {
                $columnProperties['value'] = $this->webPage->getRequestValue($name);
            }
            $columnProperties['name']   = $name;
            $this->editFormItems[$name] = $columnProperties;
            $this->addFormItems[$name]  = $columnProperties;
        }
    }

    /**
     * Vložení skriptu
     */
    function finalize()
    {
        $grid_id = $this->getTagID();
        if ($this->getTagProperty('columnsAutoSize')) {
            $this->options['onSuccess'] = 'function() { addGrid($("#'.$grid_id.'"), this)}';
            //Patch Grid Responisive
            $grid_js                    = '
        var grids=[];
            $(window).resize(function() {
                //Resize all the grids on the page
                //Only resize the ones whoes size has actually changed...
                for(var i in grids) {
                    if(grids[i].width!=grids[i].$grid.width()) {
                        sizeGrid(grids[i]);
                    }
                }
            });';
            $grid_js .='
            //Keep track of all grid elements and current sizes
            function addGrid($table, grid) {
                var $grid = $table.closest(\'.flexigrid\');
                var data = {$table:$table, $grid:$grid, grid:grid, width:$grid.width()};
                grids.push(data);
                sizeGrid(data);
            }';
            $grid_js .='
            //Make all cols with auto size fill remaining width..
            function sizeGrid(data) {
                //Auto size the middle col.
                var totalWidth = data.$grid.outerWidth()-15; //15 padding - not found where this is set

                var fixedWidth = 0;
                var fluidCols = [];
                for(var i=0; i<data.grid.colModel.length; i++ ) {
                    if( !isNaN(data.grid.colModel[i].width) ) {
                        fixedWidth+=data.$table.find(\'tr:eq(\'+i+\') td:eq(\'+i+\'):visible\').outerWidth(true);
                    } else {
                        fluidCols.push(i);
                    }
                }

                var newWidth = (totalWidth-fixedWidth)/fluidCols.length;
                for(var i in fluidCols) {
                    data.grid.g.colresize = { n:fluidCols[i], nw:newWidth };
                    data.grid.g.dragEnd( );
                }

                data.width = data.$grid.width();
            }';
        } else {
            $grid_js = '';
        }

        if ($this->select) {
            $this->options['query'] = current($this->select);
            $this->options['qtype'] = key($this->select);
        }

        if ($this->dblclk2edit) {
            $this->options['onDoubleClick'] = 'function(g) {
                    var id = $(g).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    $(location).attr(\'href\',\''.$this->dataSource->keyword.'.php?'.$this->dataSource->getMyKeyColumn().'=\' +id);

            }';
        }
        $this->options['getGridClass'] = 'function(g) { this.g=g; return g; }';
        \Ease\Shared::webPage()->addJavaScript("\n"
            .'$(\'#'.$grid_id.'\').flexigrid({ '.\Ease\JQuery\Part::partPropertiesToString($this->options).' }); '.$grid_js,
            null, true);
    }

}
