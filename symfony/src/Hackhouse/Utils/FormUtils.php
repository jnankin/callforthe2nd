<?
namespace Hackhouse\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

class FormUtils {

    public static function formPostedAndValid(Request $request, Form $form){
        if ($request->getMethod() == "POST"){
            $form->bind($request);

            return $form->isValid();
        }

        return false;
    }

    public static function getFormErrors(Form $form){
        $form = $form->createView();
        $errors = array();
        self::populateErrorsForChildren($form, $errors);
        return $errors;
    }

    private static function populateErrorsForChildren($child, &$errors){
        if ($child->hasChildren()){
            foreach($child->children as $child){
                self::populateErrorsForChildren($child, $errors);
            }
        }

        $childErrors = array();

        if (count($child->vars['errors']) > 0){
            foreach($child->vars['errors'] as $error){
                $childErrors[] = $error->getMessage();
            }
            $errors[$child->vars['id']] = $childErrors;
        }
    }

}
