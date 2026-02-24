<?php
class CompanyTemplateList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_search_company_template');
        $this->form->setFormTitle('Empresas');
        
        $name = new TEntry('name');
        $this->form->addFields([new TLabel('Nome')], [$name]);
        
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addAction('Novo', new TAction(['CompanyTemplateForm', 'onEdit']), 'fa:plus green');
        
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        
        $col_id = new TDataGridColumn('id', 'ID', 'center', 50);
        $col_name = new TDataGridColumn('name', 'Nome', 'left');
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_name);
        
        $action_edit = new TDataGridAction(['CompanyTemplateForm', 'onEdit'], ['id' => '{id}']);
        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        
        $this->datagrid->addAction($action_edit, 'Editar', 'fa:edit blue');
        $this->datagrid->addAction($action_delete, 'Excluir', 'fa:trash red');
        
        $this->datagrid->createModel();
        
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        $panel = new TPanelGroup;
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }
    
    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('CompanyTemplateList_filter', $data);
        $this->onReload();
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            
            $repository = new TRepository('CompanyTemplate');
            $criteria = new TCriteria;
            
            if ($filter = TSession::getValue('CompanyTemplateList_filter')) {
                if ($filter->name) {
                    $criteria->add(new TFilter('name', 'like', "%{$filter->name}%"));
                }
            }
            
            $criteria->setProperties($param);
            $criteria->setProperty('limit', 10);
            
            $objects = $repository->load($criteria, FALSE);
            $this->datagrid->clear();
            
            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }
            
            $this->pageNavigation->setCount($repository->count($criteria));
            $this->pageNavigation->setProperties($param);
            
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onDelete($param)
    {
        try {
            TTransaction::open('database');
            $object = CompanyTemplate::find($param['id']);
            TTransaction::close();
            
            if (!$object) {
                return;
            }
            
            $action = new TAction([$this, 'Delete']);
            $action->setParameter('id', $param['id']);
            
            new TQuestion('Deseja realmente excluir?', $action);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function Delete($param)
    {
        try {
            TTransaction::open('database');
            $object = new CompanyTemplate($param['id']);
            // Delete detail items first
            CompanyDocTemplate::where('company_template_id', '=', $object->id)->delete();
            $object->delete();
            TTransaction::close();
            $this->onReload();
            new TMessage('info', 'Registro excluído');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function show()
    {
        $this->onReload();
        parent::show();
    }
}
