<?php declare(strict_types = 1);

namespace Glingener\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Discourages the use of SitePress classes.
 */
class ForbiddenSitePressClassesSniff implements Sniff
{


    /**
     * Tokens from namespaces and class paths
     *
     * @var array
     */
    private static $namespaceTokens = array(
        T_NS_SEPARATOR,
        T_STRING,
    );

    /**
     * Tokens from PHP7 return types
     *
     * @var array
     */
    private static $returnTypeTokens = array(
        T_NS_SEPARATOR,
        T_STRING,
        T_RETURN_TYPE,
    );

    /**
     * List of native type hints to be excluded when resolving the fully qualified class name
     *
     * @var array
     */
    private static $nativeTypeHints = array(
        'void',
        'self',
        'array',
        'callable',
        'bool',
        'float',
        'int',
        'string',
    );

    /**
     * List of PHPDoc tags to check for forbidden classes
     *
     * @var array
     */
    private static $phpDocTags = array(
        '@var',
        '@param',
        '@property',
        '@return',
    );

    /**
     * List of native types used in PHPDoc
     *
     * @var array
     */
    private static $phpDocNativeTypes = array(
        'string',
        'integer',
        'int',
        'boolean',
        'bool',
        'float',
        'double',
        'object',
        'mixed',
        'array',
        'resource',
        'void',
        'null',
        'callback',
        'false',
        'true',
        'self',
        'static',
    );

    /**
     * Keep track of the current namespace
     *
     * @var string
     */
    private $currentNamespace;

    /**
     * Use statements in the current namespace
     *
     * @var array
     */
    private $useStatements = array();

    /**
     * When in a class, pointer position where the class ends
     *
     * @var integer
     */
    private $inClassUntil = -1;

    /**
     * Configurable list of forbidden classes and the alternatives to be used
     *
     * @var array
     */
    public $forbiddenClasses = array('Foo\Bar\ForbiddenClass' => 'Foo\Bar\AlternativeClass');

    public $legacyClasses = [
        'AbsoluteLinks',
        'AddTMAllowedOption',
        'Composer\\InstalledVersions',
        'ICLMenusSync',
        'ICL_AdminNotifier',
        'ICanLocalizeQuery',
        'IWPML_AJAX_Action',
        'IWPML_AJAX_Action_Loader',
        'IWPML_AJAX_Action_Run',
        'IWPML_Action',
        'IWPML_Action_Loader_Factory',
        'IWPML_Backend_Action',
        'IWPML_Backend_Action_Loader',
        'IWPML_CLI_Action',
        'IWPML_CLI_Action_Loader',
        'IWPML_Current_Language',
        'IWPML_DIC_Action',
        'IWPML_Deferred_Action_Loader',
        'IWPML_Frontend_Action',
        'IWPML_Frontend_Action_Loader',
        'IWPML_Integration_Requirements_Module',
        'IWPML_REST_Action',
        'IWPML_REST_Action_Loader',
        'IWPML_Resolve_Object_Url',
        'IWPML_TF_Collection_Filter',
        'IWPML_TF_Data_Object',
        'IWPML_TF_Settings',
        'IWPML_TM_Admin_Section',
        'IWPML_TM_Admin_Section_Factory',
        'IWPML_TM_Count',
        'IWPML_TM_Word_Calculator_Post',
        'IWPML_TM_Word_Count_Queue_Items',
        'IWPML_TM_Word_Count_Set',
        'IWPML_Taxonomy_State',
        'IWPML_Template_Service',
        'IWPML_Theme_Plugin_Localization_UI_Strategy',
        'IWPML_URL_Converter_Strategy',
        'IWPML_Upgrade_Command',
        'IWPML_WP_Element_Type',
        'Icl_Stepper',
        'OTGS_Assets_Handles',
        'OTGS_Assets_Store',
        'OTGS_UI_Assets',
        'OTGS_UI_Loader',
        'SitePress',
        'SitePressLanguageSwitcher',
        'SitePress_EditLanguages',
        'SitePress_Setup',
        'SitePress_Table',
        'SitePress_Table_Basket',
        'TranslationManagement',
        'TranslationProxy',
        'TranslationProxy_Api',
        'TranslationProxy_Basket',
        'TranslationProxy_Batch',
        'TranslationProxy_Popup',
        'TranslationProxy_Project',
        'TranslationProxy_Service',
        'TranslationProxy_Translator',
        'WPMLTranslationProxyApiException',
        'WPML\\API\\PostTypes',
        'WPML\\API\\Sanitize',
        'WPML\\API\\Settings',
        'WPML\\API\\Version',
        'WPML\\ATE\\Proxies\\Widget',
        'WPML\\AbsoluteLinks\\BlockProtector',
        'WPML\\Action\\Type',
        'WPML\\AdminLanguageSwitcher\\AdminLanguageSwitcher',
        'WPML\\AdminLanguageSwitcher\\AdminLanguageSwitcherRenderer',
        'WPML\\AdminLanguageSwitcher\\DisableWpLanguageSwitcher',
        'WPML\\AdminMenu\\Redirect',
        'WPML\\Ajax\\Endpoint\\Upload',
        'WPML\\Ajax\\Factory',
        'WPML\\Ajax\\IHandler',
        'WPML\\Ajax\\Locale',
        'WPML\\BackgroundTask\\AbstractTaskEndpoint',
        'WPML\\BackgroundTask\\BackgroundTaskLoader',
        'WPML\\BackgroundTask\\BackgroundTaskViewModel',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Model\\Label\\BothLanguages',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Model\\Label\\CurrentLanguage',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Model\\Label\\LabelTemplateInterface',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Model\\Label\\LanguageCode',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Model\\Label\\NativeLanguage',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Model\\LanguageItem',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Model\\LanguageItemTemplate',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Model\\LanguageSwitcher',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Model\\LanguageSwitcherTemplate',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Parser',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Render',
        'WPML\\BlockEditor\\Blocks\\LanguageSwitcher\\Repository',
        'WPML\\BlockEditor\\Loader',
        'WPML\\BrowserLanguageRedirect\\Dialog',
        'WPML\\CLI\\Core\\BootStrap',
        'WPML\\CLI\\Core\\Commands\\ClearCache',
        'WPML\\CLI\\Core\\Commands\\ClearCacheFactory',
        'WPML\\CLI\\Core\\Commands\\ICommand',
        'WPML\\CLI\\Core\\Commands\\IWPML_Command_Factory',
        'WPML\\CLI\\Core\\Commands\\IWPML_Core',
        'WPML\\Compatibility\\GoogleSiteKit\\Hooks',
        'WPML\\Container\\Config',
        'WPML\\Container\\Container',
        'WPML\\Convert\\Ids',
        'WPML\\Core\\BackgroundTask\\Command\\PersistBackgroundTask',
        'WPML\\Core\\BackgroundTask\\Command\\UpdateBackgroundTask',
        'WPML\\Core\\BackgroundTask\\Exception\\TaskIsNotRunnableException',
        'WPML\\Core\\BackgroundTask\\Exception\\TaskNotRunnable\\ExceededMaxRetriesException',
        'WPML\\Core\\BackgroundTask\\Exception\\TaskNotRunnable\\TaskIsCompletedException',
        'WPML\\Core\\BackgroundTask\\Exception\\TaskNotRunnable\\TaskIsPausedException',
        'WPML\\Core\\BackgroundTask\\Model\\BackgroundTask',
        'WPML\\Core\\BackgroundTask\\Model\\TaskEndpointInterface',
        'WPML\\Core\\BackgroundTask\\Repository\\BackgroundTaskRepository',
        'WPML\\Core\\BackgroundTask\\Service\\BackgroundTaskService',
        'WPML\\Core\\ISitePress',
        'WPML\\Core\\LanguageNegotiation',
        'WPML\\Core\\Menu\\Translate',
        'WPML\\Core\\PostTranslation\\SyncTranslationDocumentStatus',
        'WPML\\Core\\REST\\RewriteRules',
        'WPML\\Core\\REST\\Status',
        'WPML\\Core\\Twig\\Cache\\CacheInterface',
        'WPML\\Core\\Twig\\Cache\\FilesystemCache',
        'WPML\\Core\\Twig\\Cache\\NullCache',
        'WPML\\Core\\Twig\\Compiler',
        'WPML\\Core\\Twig\\Environment',
        'WPML\\Core\\Twig\\Error\\Error',
        'WPML\\Core\\Twig\\Error\\LoaderError',
        'WPML\\Core\\Twig\\Error\\RuntimeError',
        'WPML\\Core\\Twig\\Error\\SyntaxError',
        'WPML\\Core\\Twig\\ExpressionParser',
        'WPML\\Core\\Twig\\Extension\\AbstractExtension',
        'WPML\\Core\\Twig\\Extension\\CoreExtension',
        'WPML\\Core\\Twig\\Extension\\DebugExtension',
        'WPML\\Core\\Twig\\Extension\\EscaperExtension',
        'WPML\\Core\\Twig\\Extension\\ExtensionInterface',
        'WPML\\Core\\Twig\\Extension\\GlobalsInterface',
        'WPML\\Core\\Twig\\Extension\\InitRuntimeInterface',
        'WPML\\Core\\Twig\\Extension\\OptimizerExtension',
        'WPML\\Core\\Twig\\Extension\\ProfilerExtension',
        'WPML\\Core\\Twig\\Extension\\RuntimeExtensionInterface',
        'WPML\\Core\\Twig\\Extension\\SandboxExtension',
        'WPML\\Core\\Twig\\Extension\\StagingExtension',
        'WPML\\Core\\Twig\\Extension\\StringLoaderExtension',
        'WPML\\Core\\Twig\\FileExtensionEscapingStrategy',
        'WPML\\Core\\Twig\\Lexer',
        'WPML\\Core\\Twig\\Loader\\ArrayLoader',
        'WPML\\Core\\Twig\\Loader\\ChainLoader',
        'WPML\\Core\\Twig\\Loader\\ExistsLoaderInterface',
        'WPML\\Core\\Twig\\Loader\\FilesystemLoader',
        'WPML\\Core\\Twig\\Loader\\LoaderInterface',
        'WPML\\Core\\Twig\\Loader\\SourceContextLoaderInterface',
        'WPML\\Core\\Twig\\Markup',
        'WPML\\Core\\Twig\\NodeTraverser',
        'WPML\\Core\\Twig\\NodeVisitor\\AbstractNodeVisitor',
        'WPML\\Core\\Twig\\NodeVisitor\\EscaperNodeVisitor',
        'WPML\\Core\\Twig\\NodeVisitor\\NodeVisitorInterface',
        'WPML\\Core\\Twig\\NodeVisitor\\OptimizerNodeVisitor',
        'WPML\\Core\\Twig\\NodeVisitor\\SafeAnalysisNodeVisitor',
        'WPML\\Core\\Twig\\NodeVisitor\\SandboxNodeVisitor',
        'WPML\\Core\\Twig\\Node\\AutoEscapeNode',
        'WPML\\Core\\Twig\\Node\\BlockNode',
        'WPML\\Core\\Twig\\Node\\BlockReferenceNode',
        'WPML\\Core\\Twig\\Node\\BodyNode',
        'WPML\\Core\\Twig\\Node\\CheckSecurityNode',
        'WPML\\Core\\Twig\\Node\\CheckToStringNode',
        'WPML\\Core\\Twig\\Node\\DeprecatedNode',
        'WPML\\Core\\Twig\\Node\\DoNode',
        'WPML\\Core\\Twig\\Node\\EmbedNode',
        'WPML\\Core\\Twig\\Node\\Expression\\AbstractExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\ArrayExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\ArrowFunctionExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\AssignNameExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\AbstractBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\AddBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\AndBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\BitwiseAndBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\BitwiseOrBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\BitwiseXorBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\ConcatBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\DivBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\EndsWithBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\EqualBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\FloorDivBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\GreaterBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\GreaterEqualBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\InBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\LessBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\LessEqualBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\MatchesBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\ModBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\MulBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\NotEqualBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\NotInBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\OrBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\PowerBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\RangeBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\StartsWithBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\Binary\\SubBinary',
        'WPML\\Core\\Twig\\Node\\Expression\\BlockReferenceExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\CallExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\ConditionalExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\ConstantExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\FilterExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\Filter\\DefaultFilter',
        'WPML\\Core\\Twig\\Node\\Expression\\FunctionExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\GetAttrExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\InlinePrint',
        'WPML\\Core\\Twig\\Node\\Expression\\MethodCallExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\NameExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\NullCoalesceExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\ParentExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\TempNameExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\TestExpression',
        'WPML\\Core\\Twig\\Node\\Expression\\Test\\ConstantTest',
        'WPML\\Core\\Twig\\Node\\Expression\\Test\\DefinedTest',
        'WPML\\Core\\Twig\\Node\\Expression\\Test\\DivisiblebyTest',
        'WPML\\Core\\Twig\\Node\\Expression\\Test\\EvenTest',
        'WPML\\Core\\Twig\\Node\\Expression\\Test\\NullTest',
        'WPML\\Core\\Twig\\Node\\Expression\\Test\\OddTest',
        'WPML\\Core\\Twig\\Node\\Expression\\Test\\SameasTest',
        'WPML\\Core\\Twig\\Node\\Expression\\Unary\\AbstractUnary',
        'WPML\\Core\\Twig\\Node\\Expression\\Unary\\NegUnary',
        'WPML\\Core\\Twig\\Node\\Expression\\Unary\\NotUnary',
        'WPML\\Core\\Twig\\Node\\Expression\\Unary\\PosUnary',
        'WPML\\Core\\Twig\\Node\\FlushNode',
        'WPML\\Core\\Twig\\Node\\ForLoopNode',
        'WPML\\Core\\Twig\\Node\\ForNode',
        'WPML\\Core\\Twig\\Node\\IfNode',
        'WPML\\Core\\Twig\\Node\\ImportNode',
        'WPML\\Core\\Twig\\Node\\IncludeNode',
        'WPML\\Core\\Twig\\Node\\MacroNode',
        'WPML\\Core\\Twig\\Node\\ModuleNode',
        'WPML\\Core\\Twig\\Node\\Node',
        'WPML\\Core\\Twig\\Node\\NodeCaptureInterface',
        'WPML\\Core\\Twig\\Node\\NodeOutputInterface',
        'WPML\\Core\\Twig\\Node\\PrintNode',
        'WPML\\Core\\Twig\\Node\\SandboxNode',
        'WPML\\Core\\Twig\\Node\\SandboxedPrintNode',
        'WPML\\Core\\Twig\\Node\\SetNode',
        'WPML\\Core\\Twig\\Node\\SetTempNode',
        'WPML\\Core\\Twig\\Node\\SpacelessNode',
        'WPML\\Core\\Twig\\Node\\TextNode',
        'WPML\\Core\\Twig\\Node\\WithNode',
        'WPML\\Core\\Twig\\Parser',
        'WPML\\Core\\Twig\\Profiler\\Dumper\\BaseDumper',
        'WPML\\Core\\Twig\\Profiler\\Dumper\\BlackfireDumper',
        'WPML\\Core\\Twig\\Profiler\\Dumper\\HtmlDumper',
        'WPML\\Core\\Twig\\Profiler\\Dumper\\TextDumper',
        'WPML\\Core\\Twig\\Profiler\\NodeVisitor\\ProfilerNodeVisitor',
        'WPML\\Core\\Twig\\Profiler\\Node\\EnterProfileNode',
        'WPML\\Core\\Twig\\Profiler\\Node\\LeaveProfileNode',
        'WPML\\Core\\Twig\\Profiler\\Profile',
        'WPML\\Core\\Twig\\RuntimeLoader\\ContainerRuntimeLoader',
        'WPML\\Core\\Twig\\RuntimeLoader\\FactoryRuntimeLoader',
        'WPML\\Core\\Twig\\RuntimeLoader\\RuntimeLoaderInterface',
        'WPML\\Core\\Twig\\Sandbox\\SecurityError',
        'WPML\\Core\\Twig\\Sandbox\\SecurityNotAllowedFilterError',
        'WPML\\Core\\Twig\\Sandbox\\SecurityNotAllowedFunctionError',
        'WPML\\Core\\Twig\\Sandbox\\SecurityNotAllowedMethodError',
        'WPML\\Core\\Twig\\Sandbox\\SecurityNotAllowedPropertyError',
        'WPML\\Core\\Twig\\Sandbox\\SecurityNotAllowedTagError',
        'WPML\\Core\\Twig\\Sandbox\\SecurityPolicy',
        'WPML\\Core\\Twig\\Sandbox\\SecurityPolicyInterface',
        'WPML\\Core\\Twig\\Source',
        'WPML\\Core\\Twig\\Template',
        'WPML\\Core\\Twig\\TemplateWrapper',
        'WPML\\Core\\Twig\\Test\\IntegrationTestCase',
        'WPML\\Core\\Twig\\Test\\NodeTestCase',
        'WPML\\Core\\Twig\\Token',
        'WPML\\Core\\Twig\\TokenParser\\AbstractTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\ApplyTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\AutoEscapeTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\BlockTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\DeprecatedTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\DoTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\EmbedTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\ExtendsTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\FilterTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\FlushTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\ForTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\FromTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\IfTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\ImportTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\IncludeTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\MacroTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\SandboxTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\SetTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\SpacelessTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\TokenParserInterface',
        'WPML\\Core\\Twig\\TokenParser\\UseTokenParser',
        'WPML\\Core\\Twig\\TokenParser\\WithTokenParser',
        'WPML\\Core\\Twig\\TokenStream',
        'WPML\\Core\\Twig\\TwigFilter',
        'WPML\\Core\\Twig\\TwigFunction',
        'WPML\\Core\\Twig\\TwigTest',
        'WPML\\Core\\Twig\\Util\\DeprecationCollector',
        'WPML\\Core\\Twig\\Util\\TemplateDirIterator',
        'WPML\\Core\\Twig_Autoloader',
        'WPML\\Core\\Twig_BaseNodeVisitor',
        'WPML\\Core\\Twig_CacheInterface',
        'WPML\\Core\\Twig_Cache_Filesystem',
        'WPML\\Core\\Twig_Cache_Null',
        'WPML\\Core\\Twig_Compiler',
        'WPML\\Core\\Twig_CompilerInterface',
        'WPML\\Core\\Twig_ContainerRuntimeLoader',
        'WPML\\Core\\Twig_Environment',
        'WPML\\Core\\Twig_Error',
        'WPML\\Core\\Twig_Error_Loader',
        'WPML\\Core\\Twig_Error_Runtime',
        'WPML\\Core\\Twig_Error_Syntax',
        'WPML\\Core\\Twig_ExistsLoaderInterface',
        'WPML\\Core\\Twig_ExpressionParser',
        'WPML\\Core\\Twig_Extension',
        'WPML\\Core\\Twig_ExtensionInterface',
        'WPML\\Core\\Twig_Extension_Core',
        'WPML\\Core\\Twig_Extension_Debug',
        'WPML\\Core\\Twig_Extension_Escaper',
        'WPML\\Core\\Twig_Extension_GlobalsInterface',
        'WPML\\Core\\Twig_Extension_InitRuntimeInterface',
        'WPML\\Core\\Twig_Extension_Optimizer',
        'WPML\\Core\\Twig_Extension_Profiler',
        'WPML\\Core\\Twig_Extension_Sandbox',
        'WPML\\Core\\Twig_Extension_Staging',
        'WPML\\Core\\Twig_Extension_StringLoader',
        'WPML\\Core\\Twig_FactoryRuntimeLoader',
        'WPML\\Core\\Twig_FileExtensionEscapingStrategy',
        'WPML\\Core\\Twig_Filter',
        'WPML\\Core\\Twig_FilterCallableInterface',
        'WPML\\Core\\Twig_FilterInterface',
        'WPML\\Core\\Twig_Filter_Function',
        'WPML\\Core\\Twig_Filter_Method',
        'WPML\\Core\\Twig_Filter_Node',
        'WPML\\Core\\Twig_Function',
        'WPML\\Core\\Twig_FunctionCallableInterface',
        'WPML\\Core\\Twig_FunctionInterface',
        'WPML\\Core\\Twig_Function_Function',
        'WPML\\Core\\Twig_Function_Method',
        'WPML\\Core\\Twig_Function_Node',
        'WPML\\Core\\Twig_Lexer',
        'WPML\\Core\\Twig_LexerInterface',
        'WPML\\Core\\Twig_LoaderInterface',
        'WPML\\Core\\Twig_Loader_Array',
        'WPML\\Core\\Twig_Loader_Chain',
        'WPML\\Core\\Twig_Loader_Filesystem',
        'WPML\\Core\\Twig_Loader_String',
        'WPML\\Core\\Twig_Markup',
        'WPML\\Core\\Twig_Node',
        'WPML\\Core\\Twig_NodeCaptureInterface',
        'WPML\\Core\\Twig_NodeInterface',
        'WPML\\Core\\Twig_NodeOutputInterface',
        'WPML\\Core\\Twig_NodeTraverser',
        'WPML\\Core\\Twig_NodeVisitorInterface',
        'WPML\\Core\\Twig_NodeVisitor_Escaper',
        'WPML\\Core\\Twig_NodeVisitor_Optimizer',
        'WPML\\Core\\Twig_NodeVisitor_SafeAnalysis',
        'WPML\\Core\\Twig_NodeVisitor_Sandbox',
        'WPML\\Core\\Twig_Node_AutoEscape',
        'WPML\\Core\\Twig_Node_Block',
        'WPML\\Core\\Twig_Node_BlockReference',
        'WPML\\Core\\Twig_Node_Body',
        'WPML\\Core\\Twig_Node_CheckSecurity',
        'WPML\\Core\\Twig_Node_Deprecated',
        'WPML\\Core\\Twig_Node_Do',
        'WPML\\Core\\Twig_Node_Embed',
        'WPML\\Core\\Twig_Node_Expression',
        'WPML\\Core\\Twig_Node_Expression_Array',
        'WPML\\Core\\Twig_Node_Expression_AssignName',
        'WPML\\Core\\Twig_Node_Expression_Binary',
        'WPML\\Core\\Twig_Node_Expression_Binary_Add',
        'WPML\\Core\\Twig_Node_Expression_Binary_And',
        'WPML\\Core\\Twig_Node_Expression_Binary_BitwiseAnd',
        'WPML\\Core\\Twig_Node_Expression_Binary_BitwiseOr',
        'WPML\\Core\\Twig_Node_Expression_Binary_BitwiseXor',
        'WPML\\Core\\Twig_Node_Expression_Binary_Concat',
        'WPML\\Core\\Twig_Node_Expression_Binary_Div',
        'WPML\\Core\\Twig_Node_Expression_Binary_EndsWith',
        'WPML\\Core\\Twig_Node_Expression_Binary_Equal',
        'WPML\\Core\\Twig_Node_Expression_Binary_FloorDiv',
        'WPML\\Core\\Twig_Node_Expression_Binary_Greater',
        'WPML\\Core\\Twig_Node_Expression_Binary_GreaterEqual',
        'WPML\\Core\\Twig_Node_Expression_Binary_In',
        'WPML\\Core\\Twig_Node_Expression_Binary_Less',
        'WPML\\Core\\Twig_Node_Expression_Binary_LessEqual',
        'WPML\\Core\\Twig_Node_Expression_Binary_Matches',
        'WPML\\Core\\Twig_Node_Expression_Binary_Mod',
        'WPML\\Core\\Twig_Node_Expression_Binary_Mul',
        'WPML\\Core\\Twig_Node_Expression_Binary_NotEqual',
        'WPML\\Core\\Twig_Node_Expression_Binary_NotIn',
        'WPML\\Core\\Twig_Node_Expression_Binary_Or',
        'WPML\\Core\\Twig_Node_Expression_Binary_Power',
        'WPML\\Core\\Twig_Node_Expression_Binary_Range',
        'WPML\\Core\\Twig_Node_Expression_Binary_StartsWith',
        'WPML\\Core\\Twig_Node_Expression_Binary_Sub',
        'WPML\\Core\\Twig_Node_Expression_BlockReference',
        'WPML\\Core\\Twig_Node_Expression_Call',
        'WPML\\Core\\Twig_Node_Expression_Conditional',
        'WPML\\Core\\Twig_Node_Expression_Constant',
        'WPML\\Core\\Twig_Node_Expression_ExtensionReference',
        'WPML\\Core\\Twig_Node_Expression_Filter',
        'WPML\\Core\\Twig_Node_Expression_Filter_Default',
        'WPML\\Core\\Twig_Node_Expression_Function',
        'WPML\\Core\\Twig_Node_Expression_GetAttr',
        'WPML\\Core\\Twig_Node_Expression_MethodCall',
        'WPML\\Core\\Twig_Node_Expression_Name',
        'WPML\\Core\\Twig_Node_Expression_NullCoalesce',
        'WPML\\Core\\Twig_Node_Expression_Parent',
        'WPML\\Core\\Twig_Node_Expression_TempName',
        'WPML\\Core\\Twig_Node_Expression_Test',
        'WPML\\Core\\Twig_Node_Expression_Test_Constant',
        'WPML\\Core\\Twig_Node_Expression_Test_Defined',
        'WPML\\Core\\Twig_Node_Expression_Test_Divisibleby',
        'WPML\\Core\\Twig_Node_Expression_Test_Even',
        'WPML\\Core\\Twig_Node_Expression_Test_Null',
        'WPML\\Core\\Twig_Node_Expression_Test_Odd',
        'WPML\\Core\\Twig_Node_Expression_Test_Sameas',
        'WPML\\Core\\Twig_Node_Expression_Unary',
        'WPML\\Core\\Twig_Node_Expression_Unary_Neg',
        'WPML\\Core\\Twig_Node_Expression_Unary_Not',
        'WPML\\Core\\Twig_Node_Expression_Unary_Pos',
        'WPML\\Core\\Twig_Node_Flush',
        'WPML\\Core\\Twig_Node_For',
        'WPML\\Core\\Twig_Node_ForLoop',
        'WPML\\Core\\Twig_Node_If',
        'WPML\\Core\\Twig_Node_Import',
        'WPML\\Core\\Twig_Node_Include',
        'WPML\\Core\\Twig_Node_Macro',
        'WPML\\Core\\Twig_Node_Module',
        'WPML\\Core\\Twig_Node_Print',
        'WPML\\Core\\Twig_Node_Sandbox',
        'WPML\\Core\\Twig_Node_SandboxedPrint',
        'WPML\\Core\\Twig_Node_Set',
        'WPML\\Core\\Twig_Node_SetTemp',
        'WPML\\Core\\Twig_Node_Spaceless',
        'WPML\\Core\\Twig_Node_Text',
        'WPML\\Core\\Twig_Node_With',
        'WPML\\Core\\Twig_Parser',
        'WPML\\Core\\Twig_ParserInterface',
        'WPML\\Core\\Twig_Profiler_Dumper_Base',
        'WPML\\Core\\Twig_Profiler_Dumper_Blackfire',
        'WPML\\Core\\Twig_Profiler_Dumper_Html',
        'WPML\\Core\\Twig_Profiler_Dumper_Text',
        'WPML\\Core\\Twig_Profiler_NodeVisitor_Profiler',
        'WPML\\Core\\Twig_Profiler_Node_EnterProfile',
        'WPML\\Core\\Twig_Profiler_Node_LeaveProfile',
        'WPML\\Core\\Twig_Profiler_Profile',
        'WPML\\Core\\Twig_RuntimeLoaderInterface',
        'WPML\\Core\\Twig_Sandbox_SecurityError',
        'WPML\\Core\\Twig_Sandbox_SecurityNotAllowedFilterError',
        'WPML\\Core\\Twig_Sandbox_SecurityNotAllowedFunctionError',
        'WPML\\Core\\Twig_Sandbox_SecurityNotAllowedMethodError',
        'WPML\\Core\\Twig_Sandbox_SecurityNotAllowedPropertyError',
        'WPML\\Core\\Twig_Sandbox_SecurityNotAllowedTagError',
        'WPML\\Core\\Twig_Sandbox_SecurityPolicy',
        'WPML\\Core\\Twig_Sandbox_SecurityPolicyInterface',
        'WPML\\Core\\Twig_SimpleFilter',
        'WPML\\Core\\Twig_SimpleFunction',
        'WPML\\Core\\Twig_SimpleTest',
        'WPML\\Core\\Twig_Source',
        'WPML\\Core\\Twig_SourceContextLoaderInterface',
        'WPML\\Core\\Twig_Template',
        'WPML\\Core\\Twig_TemplateInterface',
        'WPML\\Core\\Twig_TemplateWrapper',
        'WPML\\Core\\Twig_Test',
        'WPML\\Core\\Twig_TestCallableInterface',
        'WPML\\Core\\Twig_TestInterface',
        'WPML\\Core\\Twig_Test_Function',
        'WPML\\Core\\Twig_Test_IntegrationTestCase',
        'WPML\\Core\\Twig_Test_Method',
        'WPML\\Core\\Twig_Test_Node',
        'WPML\\Core\\Twig_Test_NodeTestCase',
        'WPML\\Core\\Twig_Token',
        'WPML\\Core\\Twig_TokenParser',
        'WPML\\Core\\Twig_TokenParserBroker',
        'WPML\\Core\\Twig_TokenParserBrokerInterface',
        'WPML\\Core\\Twig_TokenParserInterface',
        'WPML\\Core\\Twig_TokenParser_AutoEscape',
        'WPML\\Core\\Twig_TokenParser_Block',
        'WPML\\Core\\Twig_TokenParser_Deprecated',
        'WPML\\Core\\Twig_TokenParser_Do',
        'WPML\\Core\\Twig_TokenParser_Embed',
        'WPML\\Core\\Twig_TokenParser_Extends',
        'WPML\\Core\\Twig_TokenParser_Filter',
        'WPML\\Core\\Twig_TokenParser_Flush',
        'WPML\\Core\\Twig_TokenParser_For',
        'WPML\\Core\\Twig_TokenParser_From',
        'WPML\\Core\\Twig_TokenParser_If',
        'WPML\\Core\\Twig_TokenParser_Import',
        'WPML\\Core\\Twig_TokenParser_Include',
        'WPML\\Core\\Twig_TokenParser_Macro',
        'WPML\\Core\\Twig_TokenParser_Sandbox',
        'WPML\\Core\\Twig_TokenParser_Set',
        'WPML\\Core\\Twig_TokenParser_Spaceless',
        'WPML\\Core\\Twig_TokenParser_Use',
        'WPML\\Core\\Twig_TokenParser_With',
        'WPML\\Core\\Twig_TokenStream',
        'WPML\\Core\\Twig_Util_DeprecationCollector',
        'WPML\\Core\\Twig_Util_TemplateDirIterator',
        'WPML\\Core\\WP\\App\\Resources',
        'WPML\\DatabaseQueries\\TranslatedPosts',
        'WPML\\DefaultCapabilities',
        'WPML\\DocPage',
        'WPML\\Element\\API\\Entity\\LanguageMapping',
        'WPML\\Element\\API\\IfOriginalPost',
        'WPML\\Element\\API\\Languages',
        'WPML\\Element\\API\\Post',
        'WPML\\Element\\API\\PostTranslations',
        'WPML\\Element\\API\\Translations',
        'WPML\\Element\\API\\TranslationsRepository',
        'WPML\\FP\\Applicative',
        'WPML\\FP\\Cast',
        'WPML\\FP\\ConstApplicative',
        'WPML\\FP\\Curryable',
        'WPML\\FP\\Debug',
        'WPML\\FP\\Either',
        'WPML\\FP\\FP',
        'WPML\\FP\\Fns',
        'WPML\\FP\\Functor\\ConstFunctor',
        'WPML\\FP\\Functor\\Functor',
        'WPML\\FP\\Functor\\IdentityFunctor',
        'WPML\\FP\\Functor\\Pointed',
        'WPML\\FP\\Invoker\\BeforeAfter',
        'WPML\\FP\\Invoker\\_Invoker',
        'WPML\\FP\\Json',
        'WPML\\FP\\Just',
        'WPML\\FP\\Left',
        'WPML\\FP\\Lens',
        'WPML\\FP\\Logic',
        'WPML\\FP\\Lst',
        'WPML\\FP\\Math',
        'WPML\\FP\\Maybe',
        'WPML\\FP\\Monoid\\All',
        'WPML\\FP\\Monoid\\Any',
        'WPML\\FP\\Monoid\\Monoid',
        'WPML\\FP\\Monoid\\Str',
        'WPML\\FP\\Monoid\\Sum',
        'WPML\\FP\\Nothing',
        'WPML\\FP\\Obj',
        'WPML\\FP\\Promise',
        'WPML\\FP\\Relation',
        'WPML\\FP\\Right',
        'WPML\\FP\\Str',
        'WPML\\FP\\System\\System',
        'WPML\\FP\\System\\_Filter',
        'WPML\\FP\\System\\_Validator',
        'WPML\\FP\\Type',
        'WPML\\FP\\Undefined',
        'WPML\\FP\\Wrapper',
        'WPML\\FullSiteEditing\\BlockTemplates',
        'WPML\\ICLToATEMigration\\Data',
        'WPML\\ICLToATEMigration\\Endpoints\\AuthenticateICL',
        'WPML\\ICLToATEMigration\\Endpoints\\DeactivateICL',
        'WPML\\ICLToATEMigration\\Endpoints\\TranslationMemory\\CheckMigrationStatus',
        'WPML\\ICLToATEMigration\\Endpoints\\TranslationMemory\\StartMigration',
        'WPML\\ICLToATEMigration\\Endpoints\\Translators\\GetFromICL',
        'WPML\\ICLToATEMigration\\Endpoints\\Translators\\GetFromICLResponseMapper',
        'WPML\\ICLToATEMigration\\Endpoints\\Translators\\Save',
        'WPML\\ICLToATEMigration\\ICLStatus',
        'WPML\\ICLToATEMigration\\Loader',
        'WPML\\Installer\\AddSiteUrl',
        'WPML\\Installer\\DisableRegisterNow',
        'WPML\\LIB\\WP\\App\\Resources',
        'WPML\\LIB\\WP\\Attachment',
        'WPML\\LIB\\WP\\Cache',
        'WPML\\LIB\\WP\\Gutenberg',
        'WPML\\LIB\\WP\\Hooks',
        'WPML\\LIB\\WP\\Http',
        'WPML\\LIB\\WP\\Nonce',
        'WPML\\LIB\\WP\\Option',
        'WPML\\LIB\\WP\\Post',
        'WPML\\LIB\\WP\\PostType',
        'WPML\\LIB\\WP\\Roles',
        'WPML\\LIB\\WP\\Transient',
        'WPML\\LIB\\WP\\Url',
        'WPML\\LIB\\WP\\User',
        'WPML\\LIB\\WP\\WPDB',
        'WPML\\LIB\\WP\\WordPress',
        'WPML\\LanguageSwitcher\\AjaxNavigation\\Hooks',
        'WPML\\LanguageSwitcher\\LsTemplateDomainUpdater',
        'WPML\\Language\\Detection\\Ajax',
        'WPML\\Language\\Detection\\Backend',
        'WPML\\Language\\Detection\\CookieLanguage',
        'WPML\\Language\\Detection\\Frontend',
        'WPML\\Language\\Detection\\Rest',
        'WPML\\Languages\\UI',
        'WPML\\MediaTranslation\\AddMediaDataToTranslationPackage',
        'WPML\\MediaTranslation\\AddMediaDataToTranslationPackageFactory',
        'WPML\\MediaTranslation\\MediaAttachmentByUrl',
        'WPML\\MediaTranslation\\MediaAttachmentByUrlFactory',
        'WPML\\MediaTranslation\\MediaCaption',
        'WPML\\MediaTranslation\\MediaCaptionTagsParse',
        'WPML\\MediaTranslation\\MediaImgParse',
        'WPML\\MediaTranslation\\MediaSettings',
        'WPML\\MediaTranslation\\MediaTranslationEditorLayout',
        'WPML\\MediaTranslation\\MediaTranslationEditorLayoutFactory',
        'WPML\\MediaTranslation\\MediaTranslationStatus',
        'WPML\\MediaTranslation\\MediaTranslationStatusFactory',
        'WPML\\MediaTranslation\\PostWithMediaFiles',
        'WPML\\MediaTranslation\\PostWithMediaFilesFactory',
        'WPML\\Media\\Duplication\\AbstractFactory',
        'WPML\\Media\\Duplication\\Hooks',
        'WPML\\Media\\Duplication\\HooksFactory',
        'WPML\\Media\\FrontendHooks',
        'WPML\\Media\\Loader',
        'WPML\\Media\\Option',
        'WPML\\Media\\Setup\\Endpoint\\PerformSetup',
        'WPML\\Media\\Setup\\Endpoint\\PrepareSetup',
        'WPML\\Media\\Translate\\Endpoint\\DuplicateFeaturedImages',
        'WPML\\Media\\Translate\\Endpoint\\FinishMediaTranslation',
        'WPML\\Media\\Translate\\Endpoint\\PrepareForTranslation',
        'WPML\\Media\\Translate\\Endpoint\\TranslateExistingMedia',
        'WPML\\Notices\\DismissNotices',
        'WPML\\Options\\Reset',
        'WPML\\Plugins',
        'WPML\\PostTranslation\\SpecialPage\\Hooks',
        'WPML\\Posts\\CountPerPostType',
        'WPML\\Posts\\DeleteTranslatedContentOfLanguages',
        'WPML\\Posts\\UntranslatedCount',
        'WPML\\REST\\XMLConfig\\Custom\\Actions',
        'WPML\\REST\\XMLConfig\\Custom\\Factory',
        'WPML\\Records\\Translations',
        'WPML\\Requirements\\WordPress',
        'WPML\\Rest\\Adaptor',
        'WPML\\Rest\\Base',
        'WPML\\Rest\\ITarget',
        'WPML\\Roles',
        'WPML\\Settings\\LanguageNegotiation',
        'WPML\\Settings\\PostType\\Automatic',
        'WPML\\Settings\\PostTypesUI',
        'WPML\\Settings\\UI',
        'WPML\\Setup\\DisableNotices',
        'WPML\\Setup\\Endpoint\\AddLanguages',
        'WPML\\Setup\\Endpoint\\AddressStep',
        'WPML\\Setup\\Endpoint\\CheckTMAllowed',
        'WPML\\Setup\\Endpoint\\CurrentStep',
        'WPML\\Setup\\Endpoint\\FinishStep',
        'WPML\\Setup\\Endpoint\\LicenseStep',
        'WPML\\Setup\\Endpoint\\RecommendedPlugins',
        'WPML\\Setup\\Endpoint\\SetOriginalLanguage',
        'WPML\\Setup\\Endpoint\\SetSecondaryLanguages',
        'WPML\\Setup\\Endpoint\\SetSupport',
        'WPML\\Setup\\Endpoint\\TranslationServices',
        'WPML\\Setup\\Endpoint\\TranslationStep',
        'WPML\\Setup\\Initializer',
        'WPML\\Setup\\Option',
        'WPML\\SuperGlobals\\Server',
        'WPML\\Support\\ATE\\Hooks',
        'WPML\\Support\\ATE\\View',
        'WPML\\Support\\ATE\\ViewFactory',
        'WPML\\TM\\API\\ATE',
        'WPML\\TM\\API\\ATE\\Account',
        'WPML\\TM\\API\\ATE\\CachedLanguageMappings',
        'WPML\\TM\\API\\ATE\\LanguageMappings',
        'WPML\\TM\\API\\Basket',
        'WPML\\TM\\API\\Batch',
        'WPML\\TM\\API\\Job\\Map',
        'WPML\\TM\\API\\Jobs',
        'WPML\\TM\\API\\TranslationServices',
        'WPML\\TM\\API\\Translators',
        'WPML\\TM\\ATE\\API\\CacheStorage\\StaticVariable',
        'WPML\\TM\\ATE\\API\\CacheStorage\\Storage',
        'WPML\\TM\\ATE\\API\\CacheStorage\\Transient',
        'WPML\\TM\\ATE\\API\\CachedATEAPI',
        'WPML\\TM\\ATE\\API\\ErrorMessages',
        'WPML\\TM\\ATE\\API\\FingerprintGenerator',
        'WPML\\TM\\ATE\\API\\RequestException',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\ActivateLanguage',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\AutoTranslate',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\CancelJobs',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\CheckLanguageSupport',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\CountJobsInProgress',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\EnableATE',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\GetATEJobsToSync',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\GetCredits',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\GetNumberOfPosts',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\Languages',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\ResumeAll',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\SetForPostType',
        'WPML\\TM\\ATE\\AutoTranslate\\Endpoint\\SyncLock',
        'WPML\\TM\\ATE\\ClonedSites\\ApiCommunication',
        'WPML\\TM\\ATE\\ClonedSites\\Endpoints\\Copy',
        'WPML\\TM\\ATE\\ClonedSites\\Endpoints\\CopyWithCredits',
        'WPML\\TM\\ATE\\ClonedSites\\Endpoints\\GetCredits',
        'WPML\\TM\\ATE\\ClonedSites\\Endpoints\\GetCredits\\AMSAPIFactory',
        'WPML\\TM\\ATE\\ClonedSites\\Endpoints\\Move',
        'WPML\\TM\\ATE\\ClonedSites\\FingerprintGeneratorForOriginalSite',
        'WPML\\TM\\ATE\\ClonedSites\\Loader',
        'WPML\\TM\\ATE\\ClonedSites\\Lock',
        'WPML\\TM\\ATE\\ClonedSites\\Report',
        'WPML\\TM\\ATE\\ClonedSites\\SecondaryDomains',
        'WPML\\TM\\ATE\\Download\\Consumer',
        'WPML\\TM\\ATE\\Download\\Job',
        'WPML\\TM\\ATE\\Download\\Process',
        'WPML\\TM\\ATE\\Factories\\Proxy',
        'WPML\\TM\\ATE\\Hooks\\JobActions',
        'WPML\\TM\\ATE\\Hooks\\JobActionsFactory',
        'WPML\\TM\\ATE\\Hooks\\LanguageMappingCache',
        'WPML\\TM\\ATE\\Hooks\\ReturnedJobActions',
        'WPML\\TM\\ATE\\Hooks\\ReturnedJobActionsFactory',
        'WPML\\TM\\ATE\\JobRecord',
        'WPML\\TM\\ATE\\JobRecords',
        'WPML\\TM\\ATE\\Jobs',
        'WPML\\TM\\ATE\\Loader',
        'WPML\\TM\\ATE\\Log\\Entry',
        'WPML\\TM\\ATE\\Log\\EventsTypes',
        'WPML\\TM\\ATE\\Log\\Hooks',
        'WPML\\TM\\ATE\\Log\\Storage',
        'WPML\\TM\\ATE\\Log\\View',
        'WPML\\TM\\ATE\\Log\\ViewFactory',
        'WPML\\TM\\ATE\\NoCreditPopup',
        'WPML\\TM\\ATE\\Proxy',
        'WPML\\TM\\ATE\\REST\\Download',
        'WPML\\TM\\ATE\\REST\\FixJob',
        'WPML\\TM\\ATE\\REST\\PublicReceive',
        'WPML\\TM\\ATE\\REST\\Retry',
        'WPML\\TM\\ATE\\REST\\Sync',
        'WPML\\TM\\ATE\\Retranslation\\Endpoint',
        'WPML\\TM\\ATE\\Retranslation\\JobsCollector',
        'WPML\\TM\\ATE\\Retranslation\\JobsCollector\\ATEResponse',
        'WPML\\TM\\ATE\\Retranslation\\RetranslationPreparer',
        'WPML\\TM\\ATE\\Retranslation\\Scheduler',
        'WPML\\TM\\ATE\\Retranslation\\SinglePageBatchHandler',
        'WPML\\TM\\ATE\\Retry\\Process',
        'WPML\\TM\\ATE\\Retry\\Result',
        'WPML\\TM\\ATE\\Retry\\Trigger',
        'WPML\\TM\\ATE\\ReturnedJobsQueue',
        'WPML\\TM\\ATE\\Review\\AcceptTranslation',
        'WPML\\TM\\ATE\\Review\\ApplyJob',
        'WPML\\TM\\ATE\\Review\\ApproveTranslations',
        'WPML\\TM\\ATE\\Review\\Cancel',
        'WPML\\TM\\ATE\\Review\\NextTranslationLink',
        'WPML\\TM\\ATE\\Review\\NonPublicCPTPreview',
        'WPML\\TM\\ATE\\Review\\PackageJob',
        'WPML\\TM\\ATE\\Review\\PreviewLink',
        'WPML\\TM\\ATE\\Review\\ReviewCompletedNotice',
        'WPML\\TM\\ATE\\Review\\ReviewStatus',
        'WPML\\TM\\ATE\\Review\\ReviewTranslation',
        'WPML\\TM\\ATE\\Review\\StatusIcons',
        'WPML\\TM\\ATE\\Review\\UpdateTranslation',
        'WPML\\TM\\ATE\\Sitekey\\Endpoint',
        'WPML\\TM\\ATE\\Sitekey\\Sync',
        'WPML\\TM\\ATE\\StatusBar',
        'WPML\\TM\\ATE\\StatusIcons',
        'WPML\\TM\\ATE\\SyncLock',
        'WPML\\TM\\ATE\\Sync\\Arguments',
        'WPML\\TM\\ATE\\Sync\\Process',
        'WPML\\TM\\ATE\\Sync\\Result',
        'WPML\\TM\\ATE\\TranslateEverything',
        'WPML\\TM\\ATE\\TranslateEverything\\Pause\\PauseAndResume',
        'WPML\\TM\\ATE\\TranslateEverything\\Pause\\UserAuthorisation',
        'WPML\\TM\\ATE\\TranslateEverything\\Pause\\View',
        'WPML\\TM\\ATE\\TranslateEverything\\TranslatableData\\Calculate',
        'WPML\\TM\\ATE\\TranslateEverything\\TranslatableData\\DataPreSetup',
        'WPML\\TM\\ATE\\TranslateEverything\\TranslatableData\\Stack',
        'WPML\\TM\\ATE\\TranslateEverything\\TranslatableData\\View',
        'WPML\\TM\\ATE\\TranslateEverything\\UntranslatedPosts',
        'WPML\\TM\\AdminBar\\Hooks',
        'WPML\\TM\\AutomaticTranslation\\Actions\\Actions',
        'WPML\\TM\\AutomaticTranslation\\Actions\\ActionsFactory',
        'WPML\\TM\\AutomaticTranslation\\Actions\\AutomaticTranslationJobCreationFailureNotice',
        'WPML\\TM\\AutomaticTranslation\\Actions\\AutomaticTranslationJobCreationFailureNoticeFactory',
        'WPML\\TM\\Container\\Config',
        'WPML\\TM\\Editor\\ATEDetailedErrorMessage',
        'WPML\\TM\\Editor\\ATERetry',
        'WPML\\TM\\Editor\\ClassicEditorActions',
        'WPML\\TM\\Editor\\Editor',
        'WPML\\TM\\Editor\\ManualJobCreationErrorNotice',
        'WPML\\TM\\Geolocalization',
        'WPML\\TM\\Jobs\\Dispatch\\BatchBuilder',
        'WPML\\TM\\Jobs\\Dispatch\\Elements',
        'WPML\\TM\\Jobs\\Dispatch\\Messages',
        'WPML\\TM\\Jobs\\Dispatch\\Packages',
        'WPML\\TM\\Jobs\\Dispatch\\Posts',
        'WPML\\TM\\Jobs\\Dispatch\\Strings',
        'WPML\\TM\\Jobs\\Endpoint\\Resign',
        'WPML\\TM\\Jobs\\ExtraFieldDataInEditor',
        'WPML\\TM\\Jobs\\ExtraFieldDataInEditorFactory',
        'WPML\\TM\\Jobs\\FieldId',
        'WPML\\TM\\Jobs\\Loader',
        'WPML\\TM\\Jobs\\Manual',
        'WPML\\TM\\Jobs\\Query\\AbstractQuery',
        'WPML\\TM\\Jobs\\Query\\CompositeQuery',
        'WPML\\TM\\Jobs\\Query\\LimitQueryHelper',
        'WPML\\TM\\Jobs\\Query\\OrderQueryHelper',
        'WPML\\TM\\Jobs\\Query\\PackageQuery',
        'WPML\\TM\\Jobs\\Query\\PostQuery',
        'WPML\\TM\\Jobs\\Query\\Query',
        'WPML\\TM\\Jobs\\Query\\QueryBuilder',
        'WPML\\TM\\Jobs\\Query\\StringQuery',
        'WPML\\TM\\Jobs\\Query\\StringsBatchQuery',
        'WPML\\TM\\Jobs\\TermMeta',
        'WPML\\TM\\Jobs\\Utils',
        'WPML\\TM\\Jobs\\Utils\\ElementLink',
        'WPML\\TM\\Jobs\\Utils\\ElementLinkFactory',
        'WPML\\TM\\Menu\\Dashboard\\PostJobsRepository',
        'WPML\\TM\\Menu\\McSetup\\CfMetaBoxOption',
        'WPML\\TM\\Menu\\PostLinkUrl',
        'WPML\\TM\\Menu\\TranslationBasket\\Strings',
        'WPML\\TM\\Menu\\TranslationBasket\\Utility',
        'WPML\\TM\\Menu\\TranslationMethod\\TranslationMethodSettings',
        'WPML\\TM\\Menu\\TranslationQueue\\CloneJobs',
        'WPML\\TM\\Menu\\TranslationQueue\\PostTypeFilters',
        'WPML\\TM\\Menu\\TranslationRoles\\RoleValidator',
        'WPML\\TM\\Menu\\TranslationServices\\ActivationAjax',
        'WPML\\TM\\Menu\\TranslationServices\\ActivationAjaxFactory',
        'WPML\\TM\\Menu\\TranslationServices\\ActiveServiceRepository',
        'WPML\\TM\\Menu\\TranslationServices\\ActiveServiceTemplate',
        'WPML\\TM\\Menu\\TranslationServices\\ActiveServiceTemplateFactory',
        'WPML\\TM\\Menu\\TranslationServices\\AuthenticationAjax',
        'WPML\\TM\\Menu\\TranslationServices\\AuthenticationAjaxFactory',
        'WPML\\TM\\Menu\\TranslationServices\\Endpoints\\Activate',
        'WPML\\TM\\Menu\\TranslationServices\\Endpoints\\Deactivate',
        'WPML\\TM\\Menu\\TranslationServices\\Endpoints\\Select',
        'WPML\\TM\\Menu\\TranslationServices\\MainLayoutTemplate',
        'WPML\\TM\\Menu\\TranslationServices\\NoSiteKeyTemplate',
        'WPML\\TM\\Menu\\TranslationServices\\Resources',
        'WPML\\TM\\Menu\\TranslationServices\\Section',
        'WPML\\TM\\Menu\\TranslationServices\\SectionFactory',
        'WPML\\TM\\Menu\\TranslationServices\\ServiceMapper',
        'WPML\\TM\\Menu\\TranslationServices\\ServicesRetriever',
        'WPML\\TM\\Menu\\TranslationServices\\Troubleshooting\\RefreshServices',
        'WPML\\TM\\Menu\\TranslationServices\\Troubleshooting\\RefreshServicesFactory',
        'WPML\\TM\\PostEditScreen\\Endpoints\\SetEditorMode',
        'WPML\\TM\\PostEditScreen\\TranslationEditorPostSettings',
        'WPML\\TM\\REST\\Base',
        'WPML\\TM\\REST\\FactoryLoader',
        'WPML\\TM\\Settings\\CustomFieldChangeDetector',
        'WPML\\TM\\Settings\\Flags\\Command\\ConvertFlags',
        'WPML\\TM\\Settings\\Flags\\Endpoints\\SetFormat',
        'WPML\\TM\\Settings\\Flags\\FlagsRepository',
        'WPML\\TM\\Settings\\Flags\\Options',
        'WPML\\TM\\Settings\\ProcessNewTranslatableFields',
        'WPML\\TM\\Settings\\Repository',
        'WPML\\TM\\StringTranslation\\StringTranslationRequest',
        'WPML\\TM\\TranslationDashboard\\EncodedFieldsValidation\\ErrorEntry',
        'WPML\\TM\\TranslationDashboard\\EncodedFieldsValidation\\FieldTitle',
        'WPML\\TM\\TranslationDashboard\\EncodedFieldsValidation\\Validator',
        'WPML\\TM\\TranslationDashboard\\Endpoints\\DisplayNeedSyncMessage',
        'WPML\\TM\\TranslationDashboard\\Endpoints\\Duplicate',
        'WPML\\TM\\TranslationDashboard\\FiltersStorage',
        'WPML\\TM\\TranslationDashboard\\SentContentMessages',
        'WPML\\TM\\TranslationProxy\\Services\\Authorization',
        'WPML\\TM\\TranslationProxy\\Services\\AuthorizationFactory',
        'WPML\\TM\\TranslationProxy\\Services\\Project\\Manager',
        'WPML\\TM\\TranslationProxy\\Services\\Project\\Project',
        'WPML\\TM\\TranslationProxy\\Services\\Project\\SiteDetails',
        'WPML\\TM\\TranslationProxy\\Services\\Project\\Storage',
        'WPML\\TM\\TranslationProxy\\Services\\Storage',
        'WPML\\TM\\Troubleshooting\\Endpoints\\ATESecondaryDomains\\EnableSecondaryDomain',
        'WPML\\TM\\Troubleshooting\\Loader',
        'WPML\\TM\\Troubleshooting\\ResetPreferredTranslationService',
        'WPML\\TM\\Troubleshooting\\SynchronizeSourceIdOfATEJobs\\TriggerSynchronization',
        'WPML\\TM\\Upgrade\\Commands\\ATEProxyUpdateRewriteRules',
        'WPML\\TM\\Upgrade\\Commands\\AddAteCommunicationRetryColumnToTranslationStatus',
        'WPML\\TM\\Upgrade\\Commands\\AddAteSyncCountToTranslationJob',
        'WPML\\TM\\Upgrade\\Commands\\AddReviewStatusColumnToTranslationStatus',
        'WPML\\TM\\Upgrade\\Commands\\CreateAteDownloadQueueTable',
        'WPML\\TM\\Upgrade\\Commands\\MigrateAteRepository',
        'WPML\\TM\\Upgrade\\Commands\\RefreshTranslationServices',
        'WPML\\TM\\Upgrade\\Commands\\ResetTranslatorOfAutomaticJobs',
        'WPML\\TM\\Upgrade\\Commands\\SynchronizeSourceIdOfATEJobs\\Command',
        'WPML\\TM\\Upgrade\\Commands\\SynchronizeSourceIdOfATEJobs\\CommandFactory',
        'WPML\\TM\\Upgrade\\Commands\\SynchronizeSourceIdOfATEJobs\\Repository',
        'WPML\\TM\\User\\Hooks',
        'WPML\\TaxonomyTermTranslation\\AutoSync',
        'WPML\\TaxonomyTermTranslation\\Hooks',
        'WPML\\Timer',
        'WPML\\TranslateLinkTargets\\Hooks',
        'WPML\\TranslationMode\\Endpoint\\SetTranslateEverything',
        'WPML\\TranslationRoles\\FindAvailableByRole',
        'WPML\\TranslationRoles\\GetManagerRecords',
        'WPML\\TranslationRoles\\GetTranslatorRecords',
        'WPML\\TranslationRoles\\Remove',
        'WPML\\TranslationRoles\\RemoveManager',
        'WPML\\TranslationRoles\\RemoveTranslator',
        'WPML\\TranslationRoles\\SaveManager',
        'WPML\\TranslationRoles\\SaveTranslator',
        'WPML\\TranslationRoles\\SaveUser',
        'WPML\\TranslationRoles\\UI\\Initializer',
        'WPML\\Troubleshooting\\AssignTranslationStatusToDuplicates',
        'WPML\\UIPage',
        'WPML\\Upgrade\\Command\\DisableOptionsAutoloading',
        'WPML\\Upgrade\\CommandsStatus',
        'WPML\\Upgrade\\Commands\\AddAutomaticColumnToIclTranslateJob',
        'WPML\\Upgrade\\Commands\\AddContextIndexToStrings',
        'WPML\\Upgrade\\Commands\\AddCountryColumnToLanguages',
        'WPML\\Upgrade\\Commands\\AddIndexToTable',
        'WPML\\Upgrade\\Commands\\AddPrimaryKeyToLocaleMap',
        'WPML\\Upgrade\\Commands\\AddPrimaryKeyToTable',
        'WPML\\Upgrade\\Commands\\AddStatusIndexToStringTranslations',
        'WPML\\Upgrade\\Commands\\AddStringPackageIdIndexToStrings',
        'WPML\\Upgrade\\Commands\\AddTranslationManagerCapToAdmin',
        'WPML\\Upgrade\\Commands\\CreateBackgroundTaskTable',
        'WPML\\Upgrade\\Commands\\DropCodeLocaleIndexFromLocaleMap',
        'WPML\\Upgrade\\Commands\\DropIndexFromTable',
        'WPML\\Upgrade\\Commands\\RemoveEndpointsOption',
        'WPML\\Upgrade\\Commands\\RemoveRestDisabledNotice',
        'WPML\\Upgrade\\Commands\\RemoveTmWcmlPromotionNotice',
        'WPML\\UrlHandling\\WPLoginUrlConverter',
        'WPML\\UrlHandling\\WPLoginUrlConverterFactory',
        'WPML\\UrlHandling\\WPLoginUrlConverterRules',
        'WPML\\User\\LanguagePairs\\ILanguagePairs',
        'WPML\\User\\UsersByCapsRepository',
        'WPML\\Utilities\\ILock',
        'WPML\\Utilities\\KeyedLock',
        'WPML\\Utilities\\Lock',
        'WPML\\Utilities\\Logger',
        'WPML\\Utilities\\NullLock',
        'WPML\\Utils\\AutoAdjustIds',
        'WPML\\Utils\\AutoAdjustIdsFactory',
        'WPML\\Utils\\DebugBackTrace',
        'WPML\\Utils\\Pager',
        'WPML\\WP\\OptionManager',
        'WPML_404_Guess',
        'WPML_AJAX_Action_Validation',
        'WPML_AJAX_Base_Factory',
        'WPML_API_Hook_Copy_Post_To_Language',
        'WPML_API_Hook_Links',
        'WPML_API_Hook_Permalink',
        'WPML_API_Hook_Sync_Custom_Fields',
        'WPML_API_Hook_Translation_Element',
        'WPML_API_Hook_Translation_Mode',
        'WPML_API_Hooks_Factory',
        'WPML_Absolute_Links_Blacklist',
        'WPML_Absolute_To_Permalinks',
        'WPML_Absolute_Url_Persisted',
        'WPML_Absolute_Url_Persisted_Filters',
        'WPML_Absolute_Url_Persisted_Filters_Factory',
        'WPML_Abstract_Job_Collection',
        'WPML_Action_Filter_Loader',
        'WPML_Active_Plugin_Provider',
        'WPML_Add_UUID_Column_To_Translation_Status',
        'WPML_Adjacent_Links_Hooks',
        'WPML_Adjacent_Links_Hooks_Factory',
        'WPML_Admin_Language_Switcher',
        'WPML_Admin_Menu_Item',
        'WPML_Admin_Menu_Root',
        'WPML_Admin_Pagination',
        'WPML_Admin_Pagination_Factory',
        'WPML_Admin_Pagination_Render',
        'WPML_Admin_Post_Actions',
        'WPML_Admin_Resources_Hooks',
        'WPML_Admin_Scripts_Setup',
        'WPML_Admin_Table_Sort',
        'WPML_Admin_URL',
        'WPML_Ajax',
        'WPML_Ajax_Factory',
        'WPML_Ajax_Response',
        'WPML_Ajax_Route',
        'WPML_Ajax_Update_Link_Targets_In_Content',
        'WPML_Ajax_Update_Link_Targets_In_Posts',
        'WPML_Ajax_Update_Link_Targets_In_Strings',
        'WPML_All_Language_Pairs',
        'WPML_All_Translation_Jobs_Migration_Notice',
        'WPML_Allowed_Redirect_Hosts',
        'WPML_Archives_Query',
        'WPML_Attachment_Action',
        'WPML_Attachment_Action_Factory',
        'WPML_Attachments_Urls_With_Identical_Slugs',
        'WPML_Attachments_Urls_With_Identical_Slugs_Factory',
        'WPML_BBPress_API',
        'WPML_BBPress_Filters',
        'WPML_Backend_Request',
        'WPML_Basket_Tab_Ajax',
        'WPML_Block_Editor_Helper',
        'WPML_Browser_Redirect',
        'WPML_Cache_Directory',
        'WPML_Cache_Factory',
        'WPML_Canonicals',
        'WPML_Canonicals_Hooks',
        'WPML_Color_Picker',
        'WPML_Comment_Duplication',
        'WPML_Compatibility_2017',
        'WPML_Compatibility_Disqus',
        'WPML_Compatibility_Disqus_Factory',
        'WPML_Compatibility_Factory',
        'WPML_Compatibility_Gutenberg',
        'WPML_Compatibility_Jetpack',
        'WPML_Compatibility_Tiny_Compress_Images',
        'WPML_Compatibility_Tiny_Compress_Images_Factory',
        'WPML_Config',
        'WPML_Config_Built_With_Page_Builders',
        'WPML_Config_Display_As_Translated',
        'WPML_Config_Shortcode_List',
        'WPML_Config_Update',
        'WPML_Config_Update_Integrator',
        'WPML_Config_Update_Log',
        'WPML_Cookie',
        'WPML_Cookie_Admin_Scripts',
        'WPML_Cookie_Admin_UI',
        'WPML_Cookie_Scripts',
        'WPML_Cookie_Setting',
        'WPML_Cookie_Setting_Ajax',
        'WPML_Copy_Once_Custom_Field',
        'WPML_Core_Privacy_Content',
        'WPML_Core_Version_Check',
        'WPML_Create_Post_Helper',
        'WPML_Current_Screen',
        'WPML_Current_Screen_Loader_Factory',
        'WPML_Custom_Columns',
        'WPML_Custom_Columns_Factory',
        'WPML_Custom_Field_Editor_Settings',
        'WPML_Custom_Field_Setting',
        'WPML_Custom_Field_Setting_Factory',
        'WPML_Custom_Field_Setting_Query',
        'WPML_Custom_Field_Setting_Query_Factory',
        'WPML_Custom_Field_XML_Settings_Import',
        'WPML_Custom_Fields_Post_Meta_Info',
        'WPML_Custom_Fields_Post_Meta_Info_Factory',
        'WPML_Custom_Types_Translation_UI',
        'WPML_Custom_XML',
        'WPML_Custom_XML_Factory',
        'WPML_Custom_XML_UI_Hooks',
        'WPML_Custom_XML_UI_Resources',
        'WPML_DB_Chunk',
        'WPML_Dashboard_Ajax',
        'WPML_Data_Encryptor',
        'WPML_Deactivate_Old_Media',
        'WPML_Deactivate_Old_Media_Factory',
        'WPML_Debug_BackTrace',
        'WPML_Debug_Information',
        'WPML_Dependencies',
        'WPML_Display_As_Translated_Attachments_Query',
        'WPML_Display_As_Translated_Attachments_Query_Factory',
        'WPML_Display_As_Translated_Default_Lang_Messages',
        'WPML_Display_As_Translated_Default_Lang_Messages_Factory',
        'WPML_Display_As_Translated_Default_Lang_Messages_View',
        'WPML_Display_As_Translated_Message_For_New_Post',
        'WPML_Display_As_Translated_Message_For_New_Post_Factory',
        'WPML_Display_As_Translated_Posts_Query',
        'WPML_Display_As_Translated_Query',
        'WPML_Display_As_Translated_Snippet_Filters',
        'WPML_Display_As_Translated_Snippet_Filters_Factory',
        'WPML_Display_As_Translated_Tax_Query',
        'WPML_Display_As_Translated_Tax_Query_Factory',
        'WPML_Display_As_Translated_Taxonomy_Query',
        'WPML_Download_Localization',
        'WPML_Duplicable_Element',
        'WPML_Editor_UI_Field',
        'WPML_Editor_UI_Field_Group',
        'WPML_Editor_UI_Field_Image',
        'WPML_Editor_UI_Field_Section',
        'WPML_Editor_UI_Fields',
        'WPML_Editor_UI_Job',
        'WPML_Editor_UI_Single_Line_Field',
        'WPML_Editor_UI_TextArea_Field',
        'WPML_Editor_UI_WYSIWYG_Field',
        'WPML_Element_Sync_Settings',
        'WPML_Element_Sync_Settings_Factory',
        'WPML_Element_Translation',
        'WPML_Element_Translation_Job',
        'WPML_Element_Translation_Package',
        'WPML_Element_Type_Translation',
        'WPML_Encoding',
        'WPML_Encoding_Validation',
        'WPML_Endpoints_Support',
        'WPML_Endpoints_Support_Factory',
        'WPML_External_Translation_Job',
        'WPML_File',
        'WPML_Fix_Links_In_Display_As_Translated_Content',
        'WPML_Fix_Type_Assignments',
        'WPML_Flags',
        'WPML_Flags_Factory',
        'WPML_Frontend_Post_Actions',
        'WPML_Frontend_Redirection',
        'WPML_Frontend_Redirection_Url',
        'WPML_Frontend_Request',
        'WPML_Frontend_Tax_Filters',
        'WPML_Full_PT_API',
        'WPML_Full_Translation_API',
        'WPML_Get_LS_Languages_Status',
        'WPML_Get_Page_By_Path',
        'WPML_Global_AJAX',
        'WPML_Google_Sitemap_Generator',
        'WPML_Hierarchy_Sync',
        'WPML_Home_Url_Filter_Context',
        'WPML_ICL_Client',
        'WPML_Inactive_Content',
        'WPML_Inactive_Content_Render',
        'WPML_Include_Url',
        'WPML_Initialize_Language_For_Post_Type',
        'WPML_Installation',
        'WPML_Installer_Domain_URL',
        'WPML_Installer_Domain_URL_Factory',
        'WPML_Installer_Gateway',
        'WPML_Integration_Requirements_Block_Editor',
        'WPML_Integrations',
        'WPML_Integrations_Requirements',
        'WPML_Integrations_Requirements_Scripts',
        'WPML_LS_Actions',
        'WPML_LS_Admin_UI',
        'WPML_LS_Assets',
        'WPML_LS_Dependencies_Factory',
        'WPML_LS_Display_As_Translated_Link',
        'WPML_LS_Footer_Slot',
        'WPML_LS_Inline_Styles',
        'WPML_LS_Languages_Cache',
        'WPML_LS_Menu_Item',
        'WPML_LS_Menu_Slot',
        'WPML_LS_Migration',
        'WPML_LS_Model_Build',
        'WPML_LS_Post_Translations_Slot',
        'WPML_LS_Public_API',
        'WPML_LS_Render',
        'WPML_LS_Settings',
        'WPML_LS_Settings_Color_Presets',
        'WPML_LS_Settings_Sanitize',
        'WPML_LS_Settings_Strings',
        'WPML_LS_Shortcode_Actions_Slot',
        'WPML_LS_Shortcodes',
        'WPML_LS_Sidebar_Slot',
        'WPML_LS_Slot',
        'WPML_LS_Slot_Factory',
        'WPML_LS_Template',
        'WPML_LS_Templates',
        'WPML_LS_Widget',
        'WPML_Lang_Domain_Filters',
        'WPML_Lang_Domains_Box',
        'WPML_Lang_Parameter_Filters',
        'WPML_Lang_URL_Validator',
        'WPML_Language',
        'WPML_Language_Code',
        'WPML_Language_Collection',
        'WPML_Language_Domain_Validation',
        'WPML_Language_Domains',
        'WPML_Language_Filter_Bar',
        'WPML_Language_Pair_Records',
        'WPML_Language_Per_Domain_SSO',
        'WPML_Language_Records',
        'WPML_Language_Resolution',
        'WPML_Language_Switcher',
        'WPML_Language_Where_Clause',
        'WPML_Languages',
        'WPML_Languages_AJAX',
        'WPML_Languages_Notices',
        'WPML_Links_Fixed_Status',
        'WPML_Links_Fixed_Status_Factory',
        'WPML_Links_Fixed_Status_For_Posts',
        'WPML_Links_Fixed_Status_For_Strings',
        'WPML_Locale',
        'WPML_Log',
        'WPML_MO_File_Search',
        'WPML_Main_Admin_Menu',
        'WPML_Media_Attachments_Duplication',
        'WPML_Media_Attachments_Duplication_Factory',
        'WPML_Media_Exception',
        'WPML_Media_Settings',
        'WPML_Media_Settings_Factory',
        'WPML_Menu_Element',
        'WPML_Menu_Item_Sync',
        'WPML_Menu_Sync_Display',
        'WPML_Menu_Sync_Functionality',
        'WPML_Meta_Boxes_Post_Edit_Ajax',
        'WPML_Meta_Boxes_Post_Edit_Ajax_Factory',
        'WPML_Meta_Boxes_Post_Edit_HTML',
        'WPML_Mobile_Detect',
        'WPML_Model_Attachments',
        'WPML_Multilingual_Options',
        'WPML_Multilingual_Options_Array_Helper',
        'WPML_Multilingual_Options_Utils',
        'WPML_Name_Query_Filter',
        'WPML_Name_Query_Filter_Translated',
        'WPML_Name_Query_Filter_Untranslated',
        'WPML_Nav_Menu',
        'WPML_Nav_Menu_Actions',
        'WPML_Non_Persistent_Cache',
        'WPML_Not_Doing_Ajax_On_Send_Exception',
        'WPML_Notice',
        'WPML_Notice_Action',
        'WPML_Notice_Render',
        'WPML_Notice_Show_On_Dashboard_And_WPML_Pages',
        'WPML_Notices',
        'WPML_PHP_Functions',
        'WPML_PHP_Version_Check',
        'WPML_Package_Translation_Job',
        'WPML_Page_Builder_Settings',
        'WPML_Page_Name_Query_Filter',
        'WPML_Plugin_Integration_Nexgen_Gallery',
        'WPML_Plugins_Check',
        'WPML_Post_Comments',
        'WPML_Post_Custom_Field_Setting',
        'WPML_Post_Custom_Field_Setting_Keys',
        'WPML_Post_Duplication',
        'WPML_Post_Edit_Ajax',
        'WPML_Post_Edit_Screen',
        'WPML_Post_Edit_Terms_Hooks',
        'WPML_Post_Edit_Terms_Hooks_Factory',
        'WPML_Post_Element',
        'WPML_Post_Hierarchy_Sync',
        'WPML_Post_Language_Filter',
        'WPML_Post_Status',
        'WPML_Post_Status_Display',
        'WPML_Post_Status_Display_Factory',
        'WPML_Post_Synchronization',
        'WPML_Post_Translation',
        'WPML_Post_Translation_Job',
        'WPML_Post_Types',
        'WPML_Posts_Listing_Page',
        'WPML_Pre_Option_Page',
        'WPML_Privacy_Content',
        'WPML_Privacy_Content_Factory',
        'WPML_Pro_Translation',
        'WPML_Queried_Object',
        'WPML_Query_Filter',
        'WPML_Query_Parser',
        'WPML_Query_Utils',
        'WPML_REST_Arguments_Sanitation',
        'WPML_REST_Arguments_Validation',
        'WPML_REST_Base',
        'WPML_REST_Extend_Args',
        'WPML_REST_Extend_Args_Factory',
        'WPML_REST_Factory_Loader',
        'WPML_REST_Posts_Hooks',
        'WPML_REST_Posts_Hooks_Factory',
        'WPML_REST_Request_Analyze',
        'WPML_REST_Request_Analyze_Factory',
        'WPML_Redirect_By_Domain',
        'WPML_Redirect_By_Param',
        'WPML_Redirect_By_Subdir',
        'WPML_Redirection',
        'WPML_Remote_String_Translation',
        'WPML_Remove_Pages_Not_In_Current_Language',
        'WPML_Request',
        'WPML_Requirements',
        'WPML_Requirements_Notification',
        'WPML_Resolve_Absolute_Url',
        'WPML_Resolve_Absolute_Url_Cached',
        'WPML_Resolve_Object_Url_Helper',
        'WPML_Resolve_Object_Url_Helper_Factory',
        'WPML_Rest',
        'WPML_Rewrite_Rules_Filter',
        'WPML_Root_Page',
        'WPML_Root_Page_Actions',
        'WPML_Rootpage_Redirect_By_Subdir',
        'WPML_SEO_HeadLangs',
        'WPML_SP_And_PT_User',
        'WPML_SP_User',
        'WPML_Save_Themes_Plugins_Localization_Options',
        'WPML_Save_Translation_Data_Action',
        'WPML_Score_Hierarchy',
        'WPML_Set_Language',
        'WPML_Settings_Filters',
        'WPML_Settings_Helper',
        'WPML_Simple_Language_Selector',
        'WPML_Site_ID',
        'WPML_Slash_Management',
        'WPML_Slug_Filter',
        'WPML_Slug_Resolution',
        'WPML_Sticky_Posts_Lang_Filter',
        'WPML_Sticky_Posts_Loader',
        'WPML_Sticky_Posts_Sync',
        'WPML_String_Functions',
        'WPML_String_Translation_Job',
        'WPML_Sunrise_Lang_In_Domains',
        'WPML_Super_Globals_Validation',
        'WPML_Support_Info',
        'WPML_Support_Info_UI',
        'WPML_Support_Info_UI_Factory',
        'WPML_Support_Page',
        'WPML_Sync_Custom_Field_Note',
        'WPML_Sync_Custom_Fields',
        'WPML_Sync_Term_Meta_Action',
        'WPML_TF_AJAX_Exception',
        'WPML_TF_Backend_AJAX_Feedback_Edit_Hooks',
        'WPML_TF_Backend_AJAX_Feedback_Edit_Hooks_Factory',
        'WPML_TF_Backend_Bulk_Actions',
        'WPML_TF_Backend_Bulk_Actions_Factory',
        'WPML_TF_Backend_Document_Information',
        'WPML_TF_Backend_Feedback_List_View',
        'WPML_TF_Backend_Feedback_List_View_Factory',
        'WPML_TF_Backend_Feedback_Row_View',
        'WPML_TF_Backend_Hooks',
        'WPML_TF_Backend_Hooks_Factory',
        'WPML_TF_Backend_Notices',
        'WPML_TF_Backend_Options_AJAX_Hooks',
        'WPML_TF_Backend_Options_AJAX_Hooks_Factory',
        'WPML_TF_Backend_Options_Hooks',
        'WPML_TF_Backend_Options_Hooks_Factory',
        'WPML_TF_Backend_Options_Scripts',
        'WPML_TF_Backend_Options_Styles',
        'WPML_TF_Backend_Options_View',
        'WPML_TF_Backend_Post_List_Hooks',
        'WPML_TF_Backend_Post_List_Hooks_Factory',
        'WPML_TF_Backend_Promote_Hooks',
        'WPML_TF_Backend_Promote_Hooks_Factory',
        'WPML_TF_Backend_Scripts',
        'WPML_TF_Backend_Styles',
        'WPML_TF_Collection',
        'WPML_TF_Collection_Filter_Factory',
        'WPML_TF_Common_Hooks',
        'WPML_TF_Common_Hooks_Factory',
        'WPML_TF_Data_Object_Post_Convert',
        'WPML_TF_Data_Object_Storage',
        'WPML_TF_Document_Information',
        'WPML_TF_Feedback',
        'WPML_TF_Feedback_Collection',
        'WPML_TF_Feedback_Collection_Filter',
        'WPML_TF_Feedback_Edit',
        'WPML_TF_Feedback_Factory',
        'WPML_TF_Feedback_Page_Filter',
        'WPML_TF_Feedback_Post_Convert',
        'WPML_TF_Feedback_Query',
        'WPML_TF_Feedback_Reviewer',
        'WPML_TF_Feedback_Status',
        'WPML_TF_Feedback_Update_Exception',
        'WPML_TF_Frontend_AJAX_Hooks',
        'WPML_TF_Frontend_AJAX_Hooks_Factory',
        'WPML_TF_Frontend_Display_Requirements',
        'WPML_TF_Frontend_Feedback_View',
        'WPML_TF_Frontend_Hooks',
        'WPML_TF_Frontend_Hooks_Factory',
        'WPML_TF_Frontend_Scripts',
        'WPML_TF_Frontend_Styles',
        'WPML_TF_Message',
        'WPML_TF_Message_Collection',
        'WPML_TF_Message_Collection_Filter',
        'WPML_TF_Message_Post_Convert',
        'WPML_TF_Module',
        'WPML_TF_Post_Rating_Metrics',
        'WPML_TF_Promote_Notices',
        'WPML_TF_Settings',
        'WPML_TF_Settings_Handler',
        'WPML_TF_Settings_Read',
        'WPML_TF_Settings_Write',
        'WPML_TF_TP_Ratings_Synchronize',
        'WPML_TF_TP_Ratings_Synchronize_Factory',
        'WPML_TF_TP_Responses',
        'WPML_TF_Translation_Queue_Hooks',
        'WPML_TF_Translation_Queue_Hooks_Factory',
        'WPML_TF_Translation_Service',
        'WPML_TF_Translation_Service_Change_Hooks',
        'WPML_TF_Translation_Service_Change_Hooks_Factory',
        'WPML_TF_WP_Cron_Events',
        'WPML_TF_WP_Cron_Events_Factory',
        'WPML_TF_XML_RPC_Feedback_Update',
        'WPML_TF_XML_RPC_Feedback_Update_Factory',
        'WPML_TF_XML_RPC_Hooks',
        'WPML_TF_XML_RPC_Hooks_Factory',
        'WPML_TM_AJAX',
        'WPML_TM_AJAX_Factory_Obsolete',
        'WPML_TM_AMS_API',
        'WPML_TM_AMS_ATE_Console_Section',
        'WPML_TM_AMS_ATE_Console_Section_Factory',
        'WPML_TM_AMS_ATE_Factories',
        'WPML_TM_AMS_Synchronize_Actions',
        'WPML_TM_AMS_Synchronize_Actions_Factory',
        'WPML_TM_AMS_Synchronize_Users_On_Access_Denied',
        'WPML_TM_AMS_Synchronize_Users_On_Access_Denied_Factory',
        'WPML_TM_AMS_Translator_Activation_Records',
        'WPML_TM_AMS_Users',
        'WPML_TM_API',
        'WPML_TM_API_Hook_Links',
        'WPML_TM_API_Hooks_Factory',
        'WPML_TM_ATE',
        'WPML_TM_ATE_AMS_Endpoints',
        'WPML_TM_ATE_API',
        'WPML_TM_ATE_API_Error',
        'WPML_TM_ATE_Authentication',
        'WPML_TM_ATE_Job',
        'WPML_TM_ATE_Job_Data_Fallback',
        'WPML_TM_ATE_Job_Data_Fallback_Factory',
        'WPML_TM_ATE_Job_Repository',
        'WPML_TM_ATE_Jobs',
        'WPML_TM_ATE_Jobs_Actions',
        'WPML_TM_ATE_Jobs_Actions_Factory',
        'WPML_TM_ATE_Jobs_Store_Actions',
        'WPML_TM_ATE_Jobs_Store_Actions_Factory',
        'WPML_TM_ATE_Models_Job_Create',
        'WPML_TM_ATE_Models_Job_File',
        'WPML_TM_ATE_Models_Language',
        'WPML_TM_ATE_Post_Edit_Actions',
        'WPML_TM_ATE_Post_Edit_Actions_Factory',
        'WPML_TM_ATE_Request_Activation_Email',
        'WPML_TM_ATE_Required_Actions_Base',
        'WPML_TM_ATE_Required_Rest_Base',
        'WPML_TM_ATE_Status',
        'WPML_TM_ATE_Translator_Login',
        'WPML_TM_ATE_Translator_Login_Factory',
        'WPML_TM_ATE_Translator_Message_Classic_Editor',
        'WPML_TM_ATE_Translator_Message_Classic_Editor_Factory',
        'WPML_TM_Action_Helper',
        'WPML_TM_Add_TP_ID_Column_To_Translation_Status',
        'WPML_TM_Add_TP_Revision_And_TS_Status_Columns_To_Core_Status',
        'WPML_TM_Add_TP_Revision_And_TS_Status_Columns_To_Translation_Status',
        'WPML_TM_Admin_Menus_Factory',
        'WPML_TM_Admin_Menus_Hooks',
        'WPML_TM_Admin_Sections',
        'WPML_TM_Ajax_Factory',
        'WPML_TM_Array_Search',
        'WPML_TM_Batch_Report',
        'WPML_TM_Batch_Report_Email_Builder',
        'WPML_TM_Batch_Report_Email_Process',
        'WPML_TM_Batch_Report_Hooks',
        'WPML_TM_Blog_Translators',
        'WPML_TM_CMS_ID',
        'WPML_TM_Count',
        'WPML_TM_Count_Composite',
        'WPML_TM_Dashboard',
        'WPML_TM_Dashboard_Display_Filter',
        'WPML_TM_Dashboard_Document_Row',
        'WPML_TM_Dashboard_Pagination',
        'WPML_TM_Default_Settings',
        'WPML_TM_Default_Settings_Factory',
        'WPML_TM_Editor_Job_Save',
        'WPML_TM_Editor_Notice',
        'WPML_TM_Editor_Save_Ajax_Action',
        'WPML_TM_Editors',
        'WPML_TM_Element_Translations',
        'WPML_TM_Email_Jobs_Summary_View',
        'WPML_TM_Email_Notification_View',
        'WPML_TM_Email_Twig_Template_Factory',
        'WPML_TM_Email_View',
        'WPML_TM_Emails_Settings',
        'WPML_TM_Emails_Settings_Factory',
        'WPML_TM_Field_Content_Action',
        'WPML_TM_Field_Type_Encoding',
        'WPML_TM_Field_Type_Sanitizer',
        'WPML_TM_General_Xliff_Import',
        'WPML_TM_General_Xliff_Reader',
        'WPML_TM_ICL20MigrationException',
        'WPML_TM_ICL20_Acknowledge',
        'WPML_TM_ICL20_Migrate',
        'WPML_TM_ICL20_Migrate_Local',
        'WPML_TM_ICL20_Migrate_Remote',
        'WPML_TM_ICL20_Migration_AJAX',
        'WPML_TM_ICL20_Migration_Container',
        'WPML_TM_ICL20_Migration_Factory',
        'WPML_TM_ICL20_Migration_Loader',
        'WPML_TM_ICL20_Migration_Locks',
        'WPML_TM_ICL20_Migration_Notices',
        'WPML_TM_ICL20_Migration_Progress',
        'WPML_TM_ICL20_Migration_Status',
        'WPML_TM_ICL20_Migration_Support',
        'WPML_TM_ICL20_Project',
        'WPML_TM_ICL20_Token',
        'WPML_TM_ICL_Translate_Job',
        'WPML_TM_ICL_Translation_Status',
        'WPML_TM_ICL_Translations',
        'WPML_TM_Job_Action',
        'WPML_TM_Job_Action_Factory',
        'WPML_TM_Job_Created',
        'WPML_TM_Job_Element_Entity',
        'WPML_TM_Job_Elements_Repository',
        'WPML_TM_Job_Entity',
        'WPML_TM_Job_Factory_User',
        'WPML_TM_Job_Layout',
        'WPML_TM_Job_TS_Status',
        'WPML_TM_Jobs_Batch',
        'WPML_TM_Jobs_Collection',
        'WPML_TM_Jobs_Daily_Summary_Report_Model',
        'WPML_TM_Jobs_Date_Range',
        'WPML_TM_Jobs_Deadline_Cron_Hooks',
        'WPML_TM_Jobs_Deadline_Cron_Hooks_Factory',
        'WPML_TM_Jobs_Deadline_Estimate',
        'WPML_TM_Jobs_Deadline_Estimate_AJAX_Action',
        'WPML_TM_Jobs_Deadline_Estimate_AJAX_Action_Factory',
        'WPML_TM_Jobs_Deadline_Estimate_Factory',
        'WPML_TM_Jobs_List_Script_Data',
        'WPML_TM_Jobs_List_Services',
        'WPML_TM_Jobs_List_Translated_By_Filters',
        'WPML_TM_Jobs_List_Translators',
        'WPML_TM_Jobs_Migration_State',
        'WPML_TM_Jobs_Needs_Update_Param',
        'WPML_TM_Jobs_Repository',
        'WPML_TM_Jobs_Search_Params',
        'WPML_TM_Jobs_Sorting_Param',
        'WPML_TM_Jobs_Summary',
        'WPML_TM_Jobs_Summary_Report',
        'WPML_TM_Jobs_Summary_Report_Hooks',
        'WPML_TM_Jobs_Summary_Report_Hooks_Factory',
        'WPML_TM_Jobs_Summary_Report_Model',
        'WPML_TM_Jobs_Summary_Report_Process',
        'WPML_TM_Jobs_Summary_Report_Process_Factory',
        'WPML_TM_Jobs_Summary_Report_View',
        'WPML_TM_Jobs_Weekly_Summary_Report_Model',
        'WPML_TM_Last_Picked_Up',
        'WPML_TM_Loader',
        'WPML_TM_Log',
        'WPML_TM_MCS_ATE',
        'WPML_TM_MCS_ATE_Strings',
        'WPML_TM_MCS_Custom_Field_Settings_Menu',
        'WPML_TM_MCS_Custom_Field_Settings_Menu_Factory',
        'WPML_TM_MCS_Pagination_Ajax',
        'WPML_TM_MCS_Pagination_Ajax_Factory',
        'WPML_TM_MCS_Pagination_Render',
        'WPML_TM_MCS_Pagination_Render_Factory',
        'WPML_TM_MCS_Post_Custom_Field_Settings_Menu',
        'WPML_TM_MCS_Search_Factory',
        'WPML_TM_MCS_Search_Render',
        'WPML_TM_MCS_Section_UI',
        'WPML_TM_MCS_Term_Custom_Field_Settings_Menu',
        'WPML_TM_Mail_Notification',
        'WPML_TM_Menus',
        'WPML_TM_Menus_Management',
        'WPML_TM_Menus_Settings',
        'WPML_TM_Old_Editor',
        'WPML_TM_Old_Editor_Factory',
        'WPML_TM_Old_Jobs_Editor',
        'WPML_TM_Only_I_Language_Pairs',
        'WPML_TM_Options_Ajax',
        'WPML_TM_Overdue_Jobs_Report',
        'WPML_TM_Overdue_Jobs_Report_Factory',
        'WPML_TM_Package_Element',
        'WPML_TM_Page',
        'WPML_TM_Parent_Filter_Ajax',
        'WPML_TM_Parent_Filter_Ajax_Factory',
        'WPML_TM_Pickup_Mode_Ajax',
        'WPML_TM_Polling_Box',
        'WPML_TM_Post',
        'WPML_TM_Post_Actions',
        'WPML_TM_Post_Data',
        'WPML_TM_Post_Edit_Custom_Field_Settings_Menu',
        'WPML_TM_Post_Edit_Link_Anchor',
        'WPML_TM_Post_Edit_Notices',
        'WPML_TM_Post_Edit_Notices_Factory',
        'WPML_TM_Post_Edit_TM_Editor_Mode',
        'WPML_TM_Post_Edit_TM_Editor_Select_Factory',
        'WPML_TM_Post_Job_Entity',
        'WPML_TM_Post_Link',
        'WPML_TM_Post_Link_Anchor',
        'WPML_TM_Post_Link_Factory',
        'WPML_TM_Post_Target_Lang_Filter',
        'WPML_TM_Post_View_Link_Anchor',
        'WPML_TM_Post_View_Link_Title',
        'WPML_TM_Privacy_Content',
        'WPML_TM_Privacy_Content_Factory',
        'WPML_TM_REST_AMS_Clients',
        'WPML_TM_REST_AMS_Clients_Factory',
        'WPML_TM_REST_ATE_API',
        'WPML_TM_REST_ATE_API_Factory',
        'WPML_TM_REST_ATE_Jobs',
        'WPML_TM_REST_ATE_Jobs_Factory',
        'WPML_TM_REST_Apply_TP_Translation',
        'WPML_TM_REST_Apply_TP_Translation_Factory',
        'WPML_TM_REST_Batch_Sync',
        'WPML_TM_REST_Batch_Sync_Factory',
        'WPML_TM_REST_Jobs',
        'WPML_TM_REST_Jobs_Factory',
        'WPML_TM_REST_TP_XLIFF',
        'WPML_TM_REST_TP_XLIFF_Factory',
        'WPML_TM_REST_XLIFF',
        'WPML_TM_REST_XLIFF_Factory',
        'WPML_TM_Record_User',
        'WPML_TM_Records',
        'WPML_TM_Requirements',
        'WPML_TM_Reset_Options_Filter',
        'WPML_TM_Reset_Options_Filter_Factory',
        'WPML_TM_Resources_Factory',
        'WPML_TM_Rest_Download_File',
        'WPML_TM_Rest_Job_Progress',
        'WPML_TM_Rest_Job_Translator_Name',
        'WPML_TM_Rest_Jobs_Columns',
        'WPML_TM_Rest_Jobs_Criteria_Parser',
        'WPML_TM_Rest_Jobs_Element_Info',
        'WPML_TM_Rest_Jobs_Language_Names',
        'WPML_TM_Rest_Jobs_Package_Helper_Factory',
        'WPML_TM_Rest_Jobs_Translation_Service',
        'WPML_TM_Rest_Jobs_View_Model',
        'WPML_TM_Restore_Skipped_Migration',
        'WPML_TM_Scripts_Factory',
        'WPML_TM_Serialized_Custom_Field_Package_Handler',
        'WPML_TM_Serialized_Custom_Field_Package_Handler_Factory',
        'WPML_TM_Service_Activation_AJAX',
        'WPML_TM_Settings_Post_Process',
        'WPML_TM_Settings_Update',
        'WPML_TM_Shortcodes_Catcher',
        'WPML_TM_Shortcodes_Catcher_Factory',
        'WPML_TM_String',
        'WPML_TM_String_Xliff_Reader',
        'WPML_TM_Support_Info',
        'WPML_TM_Support_Info_Filter',
        'WPML_TM_Sync_Installer_Wrapper',
        'WPML_TM_Sync_Jobs_Revision',
        'WPML_TM_Sync_Jobs_Status',
        'WPML_TM_TF_AJAX_Feedback_List_Hooks_Factory',
        'WPML_TM_TF_Feedback_List_Hooks',
        'WPML_TM_TF_Feedback_List_Hooks_Factory',
        'WPML_TM_TF_Module',
        'WPML_TM_TS_Instructions_Hooks',
        'WPML_TM_TS_Instructions_Hooks_Factory',
        'WPML_TM_TS_Instructions_Notice',
        'WPML_TM_Translatable_Element',
        'WPML_TM_Translatable_Element_Provider',
        'WPML_TM_Translate_Independently',
        'WPML_TM_Translated_Field',
        'WPML_TM_Translation_Basket_Dialog_Hooks',
        'WPML_TM_Translation_Basket_Dialog_View',
        'WPML_TM_Translation_Basket_Hooks_Factory',
        'WPML_TM_Translation_Batch',
        'WPML_TM_Translation_Batch_Element',
        'WPML_TM_Translation_Batch_Factory',
        'WPML_TM_Translation_Jobs_Fix_Summary',
        'WPML_TM_Translation_Jobs_Fix_Summary_Factory',
        'WPML_TM_Translation_Jobs_Fix_Summary_Notice',
        'WPML_TM_Translation_Priorities',
        'WPML_TM_Translation_Priorities_Factory',
        'WPML_TM_Translation_Priorities_Register_Action',
        'WPML_TM_Translation_Roles_Section',
        'WPML_TM_Translation_Roles_Section_Factory',
        'WPML_TM_Translation_Status',
        'WPML_TM_Translation_Status_Display',
        'WPML_TM_Translator_Note',
        'WPML_TM_Translators_Dropdown',
        'WPML_TM_Troubleshooting_Clear_TS',
        'WPML_TM_Troubleshooting_Clear_TS_UI',
        'WPML_TM_Troubleshooting_Fix_Translation_Jobs_TP_ID',
        'WPML_TM_Troubleshooting_Fix_Translation_Jobs_TP_ID_Factory',
        'WPML_TM_Troubleshooting_Reset_Pro_Trans_Config',
        'WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI',
        'WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI_Factory',
        'WPML_TM_Unsent_Jobs',
        'WPML_TM_Unsent_Jobs_Notice',
        'WPML_TM_Unsent_Jobs_Notice_Hooks',
        'WPML_TM_Unsent_Jobs_Notice_Template',
        'WPML_TM_Update_External_Translation_Data_Action',
        'WPML_TM_Update_Post_Translation_Data_Action',
        'WPML_TM_Update_Translation_Data_Action',
        'WPML_TM_Update_Translation_Status',
        'WPML_TM_Upgrade_Cancel_Orphan_Jobs',
        'WPML_TM_Upgrade_Default_Editor_For_Old_Jobs',
        'WPML_TM_Upgrade_Loader',
        'WPML_TM_Upgrade_Loader_Factory',
        'WPML_TM_Upgrade_Service_Redirect_To_Field',
        'WPML_TM_Upgrade_Translation_Priorities_For_Posts',
        'WPML_TM_Upgrade_WPML_Site_ID_ATE',
        'WPML_TM_User',
        'WPML_TM_Validate_HTML',
        'WPML_TM_WP_Query',
        'WPML_TM_Wizard_Options',
        'WPML_TM_Word_Calculator',
        'WPML_TM_Word_Calculator_Post_Custom_Fields',
        'WPML_TM_Word_Calculator_Post_Object',
        'WPML_TM_Word_Calculator_Post_Packages',
        'WPML_TM_Word_Count_Admin_Hooks',
        'WPML_TM_Word_Count_Ajax_Hooks',
        'WPML_TM_Word_Count_Background_Process',
        'WPML_TM_Word_Count_Background_Process_Factory',
        'WPML_TM_Word_Count_Background_Process_Requested_Types',
        'WPML_TM_Word_Count_Hooks_Factory',
        'WPML_TM_Word_Count_Post_Records',
        'WPML_TM_Word_Count_Process_Hooks',
        'WPML_TM_Word_Count_Queue_Items_Requested_Types',
        'WPML_TM_Word_Count_Records',
        'WPML_TM_Word_Count_Records_Factory',
        'WPML_TM_Word_Count_Refresh_Hooks',
        'WPML_TM_Word_Count_Report',
        'WPML_TM_Word_Count_Report_View',
        'WPML_TM_Word_Count_Set_Package',
        'WPML_TM_Word_Count_Set_Post',
        'WPML_TM_Word_Count_Set_String',
        'WPML_TM_Word_Count_Setters_Factory',
        'WPML_TM_Word_Count_Single_Process',
        'WPML_TM_Word_Count_Single_Process_Factory',
        'WPML_TM_XLIFF',
        'WPML_TM_XLIFF_Factory',
        'WPML_TM_XLIFF_Phase',
        'WPML_TM_XLIFF_Post_Type',
        'WPML_TM_XLIFF_Shortcodes',
        'WPML_TM_XLIFF_Translator_Notes',
        'WPML_TM_Xliff_Frontend',
        'WPML_TM_Xliff_Reader',
        'WPML_TM_Xliff_Reader_Factory',
        'WPML_TM_Xliff_Shared',
        'WPML_TM_Xliff_Writer',
        'WPML_TP_API',
        'WPML_TP_API_Batches',
        'WPML_TP_API_Client',
        'WPML_TP_API_Exception',
        'WPML_TP_API_Log_Interface',
        'WPML_TP_API_Request',
        'WPML_TP_API_Services',
        'WPML_TP_API_TF_Feedback',
        'WPML_TP_API_TF_Ratings',
        'WPML_TP_Abstract_API',
        'WPML_TP_Apply_Single_Job',
        'WPML_TP_Apply_Translation_Post_Strategy',
        'WPML_TP_Apply_Translation_Strategies',
        'WPML_TP_Apply_Translation_Strategy',
        'WPML_TP_Apply_Translation_String_Strategy',
        'WPML_TP_Apply_Translations',
        'WPML_TP_Batch',
        'WPML_TP_Batch_Exception',
        'WPML_TP_Batch_Sync_API',
        'WPML_TP_Client',
        'WPML_TP_Client_Factory',
        'WPML_TP_Exception',
        'WPML_TP_Extra_Field',
        'WPML_TP_Extra_Field_Display',
        'WPML_TP_HTTP_Request_Filter',
        'WPML_TP_Job',
        'WPML_TP_Job_Factory',
        'WPML_TP_Job_States',
        'WPML_TP_Job_Status',
        'WPML_TP_Jobs_API',
        'WPML_TP_Jobs_Collection',
        'WPML_TP_Jobs_Collection_Factory',
        'WPML_TP_Lock',
        'WPML_TP_Lock_Factory',
        'WPML_TP_Lock_Notice',
        'WPML_TP_Lock_Notice_Factory',
        'WPML_TP_Project',
        'WPML_TP_Project_API',
        'WPML_TP_Project_User',
        'WPML_TP_REST_Object',
        'WPML_TP_Refresh_Language_Pairs',
        'WPML_TP_Service',
        'WPML_TP_Services',
        'WPML_TP_String_Job',
        'WPML_TP_Sync_Ajax_Handler',
        'WPML_TP_Sync_Jobs',
        'WPML_TP_Sync_Orphan_Jobs',
        'WPML_TP_Sync_Orphan_Jobs_Factory',
        'WPML_TP_Sync_Update_Job',
        'WPML_TP_TM_Jobs',
        'WPML_TP_Translation',
        'WPML_TP_Translation_Collection',
        'WPML_TP_Translations_Repository',
        'WPML_TP_Translator',
        'WPML_TP_XLIFF_API',
        'WPML_TP_Xliff_Parser',
        'WPML_Table_Collate_Fix',
        'WPML_Tax_Menu_Loader',
        'WPML_Tax_Permalink_Filters',
        'WPML_Tax_Permalink_Filters_Factory',
        'WPML_Taxonomy_Element_Language_Dropdown',
        'WPML_Taxonomy_Translation',
        'WPML_Taxonomy_Translation_Help_Notice',
        'WPML_Taxonomy_Translation_Screen_Data',
        'WPML_Taxonomy_Translation_Sync_Display',
        'WPML_Taxonomy_Translation_Table_Display',
        'WPML_Taxonomy_Translation_UI',
        'WPML_Templates_Factory',
        'WPML_Temporary_Switch_Admin_Language',
        'WPML_Temporary_Switch_Language',
        'WPML_Term_Actions',
        'WPML_Term_Adjust_Id',
        'WPML_Term_Clauses',
        'WPML_Term_Custom_Field_Setting',
        'WPML_Term_Custom_Field_Setting_Keys',
        'WPML_Term_Display_As_Translated_Adjust_Count',
        'WPML_Term_Element',
        'WPML_Term_Filters',
        'WPML_Term_Hierarchy_Duplication',
        'WPML_Term_Hierarchy_Sync',
        'WPML_Term_Language_Filter',
        'WPML_Term_Language_Synchronization',
        'WPML_Term_Query_Filter',
        'WPML_Term_Translation',
        'WPML_Term_Translation_Utils',
        'WPML_Terms_Translations',
        'WPML_Theme_Localization_Type',
        'WPML_Theme_Plugin_Localization_Options_Ajax',
        'WPML_Theme_Plugin_Localization_Options_UI',
        'WPML_Theme_Plugin_Localization_UI',
        'WPML_Theme_Plugin_Localization_UI_Hooks',
        'WPML_Themes_Plugin_Localization_UI_Hooks_Factory',
        'WPML_Third_Party_Dependencies',
        'WPML_Transient',
        'WPML_Translate_Independently',
        'WPML_Translate_Link_Target_Global_State',
        'WPML_Translate_Link_Targets',
        'WPML_Translate_Link_Targets_Hooks',
        'WPML_Translate_Link_Targets_In_Content',
        'WPML_Translate_Link_Targets_In_Custom_Fields',
        'WPML_Translate_Link_Targets_In_Custom_Fields_Hooks',
        'WPML_Translate_Link_Targets_In_Posts',
        'WPML_Translate_Link_Targets_In_Posts_Global',
        'WPML_Translate_Link_Targets_In_Strings',
        'WPML_Translate_Link_Targets_In_Strings_Global',
        'WPML_Translate_Link_Targets_UI',
        'WPML_TranslationProxy_Com_Log',
        'WPML_TranslationProxy_Communication_Log',
        'WPML_Translation_Basket',
        'WPML_Translation_Batch',
        'WPML_Translation_Batch_Factory',
        'WPML_Translation_Editor',
        'WPML_Translation_Editor_Header',
        'WPML_Translation_Editor_Languages',
        'WPML_Translation_Editor_UI',
        'WPML_Translation_Element',
        'WPML_Translation_Element_Factory',
        'WPML_Translation_Job',
        'WPML_Translation_Job_Factory',
        'WPML_Translation_Job_Helper',
        'WPML_Translation_Job_Helper_With_API',
        'WPML_Translation_Jobs_Collection',
        'WPML_Translation_Jobs_Fixing_Migration_Ajax',
        'WPML_Translation_Jobs_Migration',
        'WPML_Translation_Jobs_Migration_Ajax',
        'WPML_Translation_Jobs_Migration_Hooks',
        'WPML_Translation_Jobs_Migration_Hooks_Factory',
        'WPML_Translation_Jobs_Migration_Notice',
        'WPML_Translation_Jobs_Migration_Repository',
        'WPML_Translation_Jobs_Missing_TP_ID_Migration_Notice',
        'WPML_Translation_Management',
        'WPML_Translation_Management_Filters_And_Actions',
        'WPML_Translation_Manager_Records',
        'WPML_Translation_Modes',
        'WPML_Translation_Proxy_API',
        'WPML_Translation_Proxy_Basket_Networking',
        'WPML_Translation_Proxy_Networking',
        'WPML_Translation_Roles_Records',
        'WPML_Translation_Selector',
        'WPML_Translation_Tree',
        'WPML_Translations',
        'WPML_Translations_Queue',
        'WPML_Translator',
        'WPML_Translator_Records',
        'WPML_Troubleshoot_Action',
        'WPML_Troubleshoot_Sync_Posts_Taxonomies',
        'WPML_Troubleshooting_Terms_Menu',
        'WPML_Twig_Template',
        'WPML_Twig_Template_Loader',
        'WPML_Twig_WP_Plugin_Extension',
        'WPML_UI_Help_Tab',
        'WPML_UI_Pagination',
        'WPML_UI_Screen_Options_Factory',
        'WPML_UI_Screen_Options_Pagination',
        'WPML_UI_Unlock_Button',
        'WPML_URL_Cached_Converter',
        'WPML_URL_Converter',
        'WPML_URL_Converter_Abstract_Strategy',
        'WPML_URL_Converter_CPT',
        'WPML_URL_Converter_Domain_Strategy',
        'WPML_URL_Converter_Factory',
        'WPML_URL_Converter_Lang_Param_Helper',
        'WPML_URL_Converter_Parameter_Strategy',
        'WPML_URL_Converter_Subdir_Strategy',
        'WPML_URL_Converter_Url_Helper',
        'WPML_URL_Converter_User',
        'WPML_URL_Filters',
        'WPML_URL_HTTP_Referer',
        'WPML_URL_HTTP_Referer_Factory',
        'WPML_UUID',
        'WPML_Update_PickUp_Method',
        'WPML_Update_Term_Action',
        'WPML_Update_Term_Count',
        'WPML_Upgrade',
        'WPML_Upgrade_Add_Column_To_Table',
        'WPML_Upgrade_Add_Editor_Column_To_Icl_Translate_Job',
        'WPML_Upgrade_Add_Location_Column_To_Strings',
        'WPML_Upgrade_Add_Word_Count_Column_To_Strings',
        'WPML_Upgrade_Add_Wrap_Column_To_Strings',
        'WPML_Upgrade_Add_Wrap_Column_To_Translate',
        'WPML_Upgrade_Admin_Users_Languages',
        'WPML_Upgrade_Chinese_Flags',
        'WPML_Upgrade_Command_Definition',
        'WPML_Upgrade_Command_Factory',
        'WPML_Upgrade_Display_Mode_For_Posts',
        'WPML_Upgrade_Element_Type_Length_And_Collation',
        'WPML_Upgrade_Fix_Non_Admin_With_Admin_Cap',
        'WPML_Upgrade_Loader',
        'WPML_Upgrade_Loader_Factory',
        'WPML_Upgrade_Localization_Files',
        'WPML_Upgrade_Media_Duplication_In_Core',
        'WPML_Upgrade_Media_Without_Language',
        'WPML_Upgrade_Remove_Translation_Services_Transient',
        'WPML_Upgrade_Run_All',
        'WPML_Upgrade_Schema',
        'WPML_Upgrade_Table_Translate_Job_For_3_9_0',
        'WPML_Upgrade_WPML_Site_ID',
        'WPML_Upgrade_WPML_Site_ID_Remaining',
        'WPML_User',
        'WPML_User_Admin_Language',
        'WPML_User_Jobs_Notification_Settings',
        'WPML_User_Jobs_Notification_Settings_Render',
        'WPML_User_Jobs_Notification_Settings_Template',
        'WPML_User_Language',
        'WPML_User_Language_Switcher',
        'WPML_User_Language_Switcher_Hooks',
        'WPML_User_Language_Switcher_Resources',
        'WPML_User_Language_Switcher_UI',
        'WPML_User_Options_Menu',
        'WPML_Users_Languages',
        'WPML_Users_Languages_Dependencies',
        'WPML_Verify_SitePress_Settings',
        'WPML_WPDB_And_SP_User',
        'WPML_WPDB_User',
        'WPML_WP_API',
        'WPML_WP_Cache',
        'WPML_WP_Cache_Factory',
        'WPML_WP_Cache_Item',
        'WPML_WP_Comments',
        'WPML_WP_Cron_Check',
        'WPML_WP_In_Subdir_URL_Filters',
        'WPML_WP_In_Subdir_URL_Filters_Factory',
        'WPML_WP_Option',
        'WPML_WP_Options_General_Hooks',
        'WPML_WP_Options_General_Hooks_Factory',
        'WPML_WP_Post',
        'WPML_WP_Post_Type',
        'WPML_WP_Query_API',
        'WPML_WP_Roles',
        'WPML_WP_Taxonomy',
        'WPML_WP_Taxonomy_Query',
        'WPML_WP_User_Factory',
        'WPML_WP_User_Query_Factory',
        'WPML_Whip_Requirements',
        'WPML_Widgets_Support_Backend',
        'WPML_Widgets_Support_Factory',
        'WPML_Widgets_Support_Frontend',
        'WPML_WordPress_Actions',
        'WPML_XDomain_Data_Parser',
        'WPML_XML2Array',
        'WPML_XMLRPC',
        'WPML_XML_Config_Log_Factory',
        'WPML_XML_Config_Log_Notice',
        'WPML_XML_Config_Log_UI',
        'WPML_XML_Config_Read',
        'WPML_XML_Config_Read_File',
        'WPML_XML_Config_Read_Option',
        'WPML_XML_Config_Validate',
        'WPML_XML_Transform',
        'WP_Async_Request',
        'WP_Background_Process',
        'Whip_BasicMessage',
        'Whip_Configuration',
        'Whip_DismissStorage',
        'Whip_EmptyProperty',
        'Whip_Host',
        'Whip_HostMessage',
        'Whip_InvalidOperatorType',
        'Whip_InvalidType',
        'Whip_InvalidVersionComparisonString',
        'Whip_InvalidVersionRequirementMessage',
        'Whip_Listener',
        'Whip_Message',
        'Whip_MessageDismisser',
        'Whip_MessageFormatter',
        'Whip_MessagePresenter',
        'Whip_MessagesManager',
        'Whip_NullMessage',
        'Whip_Requirement',
        'Whip_RequirementsChecker',
        'Whip_UpgradePhpMessage',
        'Whip_VersionDetector',
        'Whip_VersionRequirement',
        'Whip_WPDismissOption',
        'Whip_WPMessageDismissListener',
        'Whip_WPMessagePresenter',
        'icl_cache',
        'wpml_zip'
    ];
    /**
     * The usages that are forbidden
     *
     * @var array
     */
    public $usages = array(
        'extends',
        'implements',
        'new',
        'static-call',
        'trait-use',
        'type-hint',
        'phpdoc',
    );

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var boolean
     */
    public $error = true;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        $tokens = array(
            T_NAMESPACE,
            T_USE,
            T_CLASS,
        );

        if (in_array('extends', $this->usages) === true) {
            $tokens[] = T_EXTENDS;
        }

        if (in_array('implements', $this->usages) === true) {
            $tokens[] = T_IMPLEMENTS;
        }

        if (in_array('new', $this->usages) === true) {
            $tokens[] = T_NEW;
        }

        if (in_array('static-call', $this->usages) === true) {
            $tokens[] = T_DOUBLE_COLON;
        }

        if (in_array('type-hint', $this->usages) === true) {
            $tokens[] = T_FUNCTION;
            $tokens[] = T_CLOSURE;
        }

        if (in_array('phpdoc', $this->usages) === true) {
            $tokens[] = T_DOC_COMMENT_TAG;
        }

        return $tokens;

    }//end register()


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in
     *                                               the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_NAMESPACE) {
            list($this->currentNamespace) = $this->getNextContent($tokens, ($stackPtr + 1), self::$namespaceTokens, array(T_WHITESPACE));
            $this->useStatements          = array();
            return;
        }

        if ($tokens[$stackPtr]['code'] === T_USE) {
            $notInClass = $stackPtr > $this->inClassUntil;
            if ($notInClass === true) {
                // We're outside of a class definition. Use statements are class imports.
                $useSemiColonPtr = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
                $useStartPtr     = $stackPtr;
                do {
                    list($useNamespace) = $this->getNextContent($tokens, ($useStartPtr + 1), self::$namespaceTokens, array(T_WHITESPACE));

                    // Check if there is an alias defined for that use statement.
                    $aliasTokenPtr = $phpcsFile->findNext(array_merge(self::$namespaceTokens, array(T_WHITESPACE)), ($useStartPtr + 1), null, true);
                    if ($aliasTokenPtr !== false && $tokens[$aliasTokenPtr]['code'] === T_AS) {
                        list($alias) = $this->getNextContent($tokens, ($aliasTokenPtr + 1), array(T_STRING), array(T_WHITESPACE));
                    } else {
                        $alias            = $useNamespace;
                        $lastBackslashPos = strrpos($useNamespace, '\\');
                        if ($lastBackslashPos !== false) {
                            // Take the alias from the class path.
                            $alias = substr($useNamespace, ($lastBackslashPos + 1));
                        }
                    }

                    $this->useStatements[$alias] = $useNamespace;

                    // Find start position of the next import statement.
                    $useStartPtr = $phpcsFile->findNext(T_COMMA, ($useStartPtr + 1));
                } while ($useStartPtr !== false && $useStartPtr < $useSemiColonPtr);

                return;
            }//end if

            if (in_array('trait-use', $this->usages) === true) {
                // We're in a class definition. Use statements are trait imports.
                $useSemiColonPtr = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
                $useStartPtr     = $stackPtr;
                do {
                    list($traitClass, $traitClassPtr) = $this->getNextContent($tokens, ($useStartPtr + 1), self::$namespaceTokens, array(T_WHITESPACE));
                    $fullyQualifiedClassName          = $this->getFullyQualifiedClassName($traitClass);
                    $this->checkClassName($phpcsFile, $fullyQualifiedClassName, $traitClassPtr);

                    // Find start position of the next trait import statement.
                    $useStartPtr = $phpcsFile->findNext(T_COMMA, ($useStartPtr + 1));
                } while ($useStartPtr !== false && $useStartPtr < $useSemiColonPtr);

                return;
            }
        }//end if

        // Detect if we're in a class definition. Then, use statements have to be interpreted as Trait imports.
        if ($tokens[$stackPtr]['code'] === T_CLASS) {
            $this->inClassUntil = $tokens[$stackPtr]['scope_closer'];
            return;
        }

        if (in_array('new', $this->usages) === true && $tokens[$stackPtr]['code'] === T_NEW) {
            list($className, $classNamePtr) = $this->getNextContent($tokens, ($stackPtr + 1), self::$namespaceTokens, array(T_WHITESPACE));
            $fullyQualifiedClassName        = $this->getFullyQualifiedClassName($className);
            $this->checkClassName($phpcsFile, $fullyQualifiedClassName, $classNamePtr);
            return;
        }

        if (in_array('extends', $this->usages) === true && $tokens[$stackPtr]['code'] === T_EXTENDS) {
            list($className, $classNamePtr) = $this->getNextContent($tokens, ($stackPtr + 1), self::$namespaceTokens, array(T_WHITESPACE));
            $fullyQualifiedClassName        = $this->getFullyQualifiedClassName($className);
            $this->checkClassName($phpcsFile, $fullyQualifiedClassName, $classNamePtr);
            return;
        }

        if (in_array('implements', $this->usages) === true && $tokens[$stackPtr]['code'] === T_IMPLEMENTS) {
            $implementsEndPtr   = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, ($stackPtr + 1));
            $implementsStartPtr = $stackPtr;
            do {
                list($implementsClass, $implementsClassPtr) = $this->getNextContent($tokens, ($implementsStartPtr + 1), self::$namespaceTokens, array(T_WHITESPACE));
                $fullyQualifiedClassName = $this->getFullyQualifiedClassName($implementsClass);
                $this->checkClassName($phpcsFile, $fullyQualifiedClassName, $implementsClassPtr);

                // Find start position of the next trait-use statement.
                $implementsStartPtr = $phpcsFile->findNext(T_COMMA, ($implementsStartPtr + 1));
            } while ($implementsStartPtr !== false && $implementsStartPtr < $implementsEndPtr);

            return;
        }

        if (in_array('static-call', $this->usages) === true && $tokens[$stackPtr]['code'] === T_DOUBLE_COLON) {
            list($className, $classNamePtr) = $this->getPrevContent($tokens, ($stackPtr - 1), self::$namespaceTokens, array(T_WHITESPACE));
            $fullyQualifiedClassName        = $this->getFullyQualifiedClassName($className);
            $this->checkClassName($phpcsFile, $fullyQualifiedClassName, $classNamePtr);
            return;
        }

        if (in_array('type-hint', $this->usages) === true && $tokens[$stackPtr]['code'] === T_FUNCTION || $tokens[$stackPtr]['code'] === T_CLOSURE) {
            $ignoreTokens = Tokens::$emptyTokens;
            // Call by reference.
            $ignoreTokens[] = T_BITWISE_AND;
            $ignoreTokens[] = T_STRING;

            $openBracket = $phpcsFile->findNext($ignoreTokens, ($stackPtr + 1), null, true);
            if ($tokens[$openBracket]['code'] !== T_OPEN_PARENTHESIS) {
                return;
            }

            if (isset($tokens[$openBracket]['parenthesis_closer']) === false) {
                return;
            }

            $closeBracket = $tokens[$openBracket]['parenthesis_closer'];
            for ($i = ($openBracket + 1); $i <= $closeBracket; $i = ($endOfTypeHintPtr + 1)) {
                $endOfTypeHintPtr = $phpcsFile->findNext(T_VARIABLE, $i);
                if ($endOfTypeHintPtr === false || $endOfTypeHintPtr > $closeBracket) {
                    break;
                }

                list($typeHint, $typeHintPtr) = $this->getPrevContent($tokens, ($endOfTypeHintPtr - 1), self::$namespaceTokens, array(T_WHITESPACE, T_BITWISE_AND));
                if (strlen($typeHint) > 0) {
                    $fullyQualifiedClassName = $this->getFullyQualifiedClassName($typeHint);
                    $this->checkClassName($phpcsFile, $fullyQualifiedClassName, $typeHintPtr);
                }
            }

            // Check for PHP7 return type hint.
            $colonTokenPtr = $phpcsFile->findNext(array_merge(self::$namespaceTokens, array(T_WHITESPACE)), ($closeBracket + 1), null, true);
            if ($colonTokenPtr !== false && $tokens[$colonTokenPtr]['code'] === T_COLON) {
                list($returnType, $returnTypePtr) = $this->getNextContent($tokens, ($colonTokenPtr + 1), self::$returnTypeTokens, array(T_WHITESPACE));
                $fullyQualifiedClassName          = $this->getFullyQualifiedClassName($returnType);
                $this->checkClassName($phpcsFile, $fullyQualifiedClassName, $returnTypePtr);
            }

            return;
        }//end if

        if (in_array('phpdoc', $this->usages) === true && $tokens[$stackPtr]['code'] === T_DOC_COMMENT_TAG) {
            if (in_array($tokens[$stackPtr]['content'], self::$phpDocTags) !== true) {
                return;
            }

            $phpDocStrPtr = ($stackPtr + 2);
            if ($tokens[$phpDocStrPtr]['code'] === T_DOC_COMMENT_STRING) {
                preg_match('/^([^$&.\s]+)/', $tokens[$phpDocStrPtr]['content'], $matches);
                if (isset($matches[1]) === true) {
                    $phpDocTypes = explode('|', $matches[1]);
                    foreach ($phpDocTypes as $phpDocType) {
                        if (in_array($phpDocType, self::$phpDocNativeTypes) === true) {
                            // Do not check native PHPDoc types.
                            continue;
                        }

                        if (substr($phpDocType, -2) === '[]') {
                            // Get type from array.
                            $phpDocType = substr($phpDocType, 0, (strlen($phpDocType) - 2));
                        }

                        $fullyQualifiedClassName = $this->getFullyQualifiedClassName($phpDocType);
                        $this->checkClassName($phpcsFile, $fullyQualifiedClassName, $phpDocStrPtr);
                    }
                }
            }//end if

            return;
        }//end if

    }//end process()


    /**
     * Get string of allowed tokens next from a certain position
     *
     * @param array $tokens        The token stream.
     * @param int   $startPtr      The start position in the token stream.
     * @param array $allowedTokens The allowed tokens to retrieve content from.
     * @param array $skipTokens    Tokens to be skipped at the beginning.
     *
     * @return array
     */
    private function getPrevContent($tokens, $startPtr, $allowedTokens, $skipTokens)
    {
        $i = $startPtr;
        for (; $i >= 0; $i--) {
            if (in_array($tokens[$i]['code'], $skipTokens) === false) {
                break;
            }
        }

        $stringStartPtr = $i;
        $string         = '';
        for (; $i >= 0; $i--) {
            if (in_array($tokens[$i]['code'], $allowedTokens) === true) {
                $string         = $tokens[$i]['content'].$string;
                $stringStartPtr = $i;
            } else {
                break;
            }
        }

        return array(
            $string,
            $stringStartPtr,
        );

    }//end getPrevContent()


    /**
     * Get string of allowed tokens previous from a certain position
     *
     * @param array $tokens        The token stream.
     * @param int   $startPtr      The start position in the token stream.
     * @param array $allowedTokens The allowed tokens to retrieve content from.
     * @param array $skipTokens    Tokens to be skipped at the beginning.
     *
     * @return array
     */
    private function getNextContent($tokens, $startPtr, $allowedTokens, $skipTokens)
    {
        $numTokens = count($tokens);
        $i         = $startPtr;
        for (; $i < $numTokens; $i++) {
            if (in_array($tokens[$i]['code'], $skipTokens) === false) {
                break;
            }
        }

        $stringStartPtr = $i;
        $string         = '';
        for (; $i < $numTokens; $i++) {
            if (in_array($tokens[$i]['code'], $allowedTokens) === true) {
                $string .= $tokens[$i]['content'];
            } else {
                break;
            }
        }

        return array(
            $string,
            $stringStartPtr,
        );

    }//end getNextContent()


    /**
     * Get the fully qualified class name
     *
     * @param string $className The class name to resolve fully qualified class name.
     *
     * @return string
     */
    private function getFullyQualifiedClassName($className)
    {
        if (in_array($className, self::$nativeTypeHints) === true) {
            return $className;
        }

        if (isset($className[0]) === true && $className[0] === '\\') {
            return substr($className, 1);
        }

        $nsSeparatorPos = strpos($className, '\\');
        if ($nsSeparatorPos === false) {
            if (isset($this->useStatements[$className]) === true) {
                return $this->useStatements[$className];
            } else {
                return $this->currentNamespace.'\\'.$className;
            }
        }

        $nsFirstPart      = substr($className, 0, $nsSeparatorPos);
        $nsRemainingParts = substr($className, ($nsSeparatorPos + 1));
        if (isset($this->useStatements[$nsFirstPart]) === true) {
            $classPath = $this->useStatements[$nsFirstPart];
            if (strlen($nsRemainingParts) > 0) {
                $classPath .= '\\'.$nsRemainingParts;
            }

            return $classPath;
        } else {
            return $this->currentNamespace.'\\'.$className;
        }

    }//end getFullyQualifiedClassName()


    /**
     * Check if a class name is forbidden and add an error
     *
     * @param File   $phpcsFile The file being scanned.
     * @param string $className The class name to check if forbidden.
     * @param int    $stackPtr  The position of the forbidden className in the token array.
     *
     * @return void
     */
    private function checkClassName(File $phpcsFile, $className, $stackPtr)
    {
        if (in_array( $className, $this->legacyClasses ) ) {
            $error = 'The use of the legacy class %s() is forbidden. ';
            $error .= " It's only allowed in src/Legacy/.*";
            $phpcsFile->addError( $error, $stackPtr, 'Found', [ $className ] );
            return;
        }
        if (isset($this->forbiddenClasses[$className]) === true) {
            $this->addError($phpcsFile, $stackPtr, $className);
        }

    }//end checkClassName()


    /**
     * Generates the error or warning for this sniff.
     *
     * @param File   $phpcsFile The file being scanned.
     * @param int    $stackPtr  The position of the forbidden className in the token array.
     * @param string $className The name of the forbidden class.
     *
     * @return void
     */
    protected function addError($phpcsFile, $stackPtr, $className)
    {
        $data  = array($className);
        $error = 'The use of className %s() is ';
        if ($this->error === true) {
            $type   = 'Found';
            $error .= 'forbidden';
        } else {
            $type   = 'Discouraged';
            $error .= 'discouraged';
        }

        if ($this->forbiddenClasses[$className] !== null
            && $this->forbiddenClasses[$className] !== 'null'
    ) {
            $type  .= 'WithAlternative';
            $data[] = $this->forbiddenClasses[$className];
            $error .= '; use %s() instead';
        }

        if ($this->error === true) {
            $phpcsFile->addError($error, $stackPtr, $type, $data);
        } else {
            $phpcsFile->addWarning($error, $stackPtr, $type, $data);
        }

    }//end addError()


}//end class
