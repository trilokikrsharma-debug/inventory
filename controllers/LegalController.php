<?php
/**
 * LegalController – Public legal pages (Privacy, Terms, Refund)
 */
class LegalController extends Controller {
    protected $allowedActions = ['index'];

    public function index() {
        $page = $_GET['page'] ?? '';
        switch ($page) {
            case 'privacy':
                $this->renderPartial('public.privacy', []);
                break;
            case 'terms':
                $this->renderPartial('public.terms', []);
                break;
            case 'refund':
                $this->renderPartial('public.refund', []);
                break;
            default:
                $this->redirect('/');
        }
    }
}
