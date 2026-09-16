/**
 * "Ошибка одного блока не должна ломать весь экран" (docs/main_screen.md
 * §16, требование 10) — обычный `componentDidCatch`, оборачивает каждую
 * панель HUD отдельно в MainScreenHud.tsx, чтобы исключение в одной не
 * снесло разметку остальных.
 */
import { Component, type ComponentChildren } from 'preact';

interface Props {
  label: string;
  children: ComponentChildren;
}

interface State {
  error: Error | null;
}

export class HudErrorBoundary extends Component<Props, State> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error): void {
    console.error(`[HUD] ошибка в блоке "${this.props.label}":`, error);
  }

  render() {
    if (this.state.error) {
      return <div class="ds-hud-error-block" title={this.state.error.message}>⚠ {this.props.label}</div>;
    }
    return this.props.children;
  }
}
