package util;

import java.util.Collection;
import java.util.Iterator;
import java.util.LinkedList;
import java.util.NoSuchElementException;

public class StationSelectionIterator<E>
implements Iterator<E> {
	private LinkedList<E> list;
	private boolean connect;
	private int index;
	private int emptyListValue;

	public StationSelectionIterator(Collection<E> list, boolean connect) {
		this.list = new LinkedList<E>(list);
		this.connect = connect;
		if (connect) {
			this.index = 0;
			this.emptyListValue = list.size();			
		}
		else {
			this.index = list.size()-1;
			this.emptyListValue = -1;
		}
	}

	public boolean hasNext() {
		return !(this.index==this.emptyListValue);
	}

	public E next() {
		if (!this.hasNext()) {
			throw new NoSuchElementException();
		}
		E value = this.list.get(index);
		if (this.connect) {
			this.index++;
		}
		else {
			this.index--;
		}
		return value;
	}

	public void remove() {
		throw new UnsupportedOperationException();
	}
}
