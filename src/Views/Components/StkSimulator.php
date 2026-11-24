<?php

namespace Nyumbani\Ui\Views\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class StkSimulator extends Component
{
    /**
     * Create the simulator, whose buttons answer for Safaricom: pass the contract of the attempt the page follows. The urls default to the routes nyumbani/daraja registers while DARAJA_STK_SIMULATE is on
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function __construct(
        public array $attempt,
        public ?string $stkUrl = null,
        public ?string $confirmationUrl = null,
    ) {
        //  Define the stk url
        $this->stkUrl ??= Route::has('daraja.simulate.stk')
            ? route('daraja.simulate.stk')
            : null;

        //  Define the confirmation url
        $this->confirmationUrl ??= Route::has('daraja.simulate.confirmation')
            ? route('daraja.simulate.confirmation')
            : null;
    }

    /**
     * Render nothing unless pushes are simulated and there are routes to post to, so the panel can stay in a view and vanish outside simulation
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function shouldRender(): bool
    {
        return ! app()->isProduction()
            && config('daraja.stk.simulate')
            && $this->stkUrl !== null
            && $this->confirmationUrl !== null;
    }

    /**
     * Get the view
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function render(): View
    {
        return view('nyumbani::components.stk-simulator');
    }
}
